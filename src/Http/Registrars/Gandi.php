<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Http\Registrars;

use CranleighSchool\DomainExpiry\Exceptions\RegistrarImportException;
use CranleighSchool\DomainExpiry\RegistrarInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Gandi implements RegistrarInterface
{
    public function shouldTry(): bool
    {
        return ! empty(config('domain-expiry.registrars.gandi.auth.token'));
    }

    private function request(): PendingRequest
    {
        return Http::withToken(config('domain-expiry.registrars.gandi.auth.token'))
            ->baseUrl('https://api.gandi.net/v5/');
    }

    /**
     * @throws RegistrarImportException
     */
    public function getDomains(): Collection
    {
        try {
            return $this->request()
                ->get('domain/domains')
                ->throw()
                ->collect()
                ->pluck('fqdn');
        } catch (RequestException|ConnectionException $e) {
            throw new RegistrarImportException('Gandi API request failed.', previous: $e);
        }
    }

    /**
     * @return Collection <string, float>
     */
    public function renewalCosts(): Collection
    {
        return Cache::remember('gandi-renewal-costs', now()->addHours(12), function () {
            return $this->fetchRenewalCosts();
        });
    }
    private function fetchRenewalCosts(): Collection
    {
        $tlds = $this->request()
            ->get('domain/domains')
            ->throw()
            ->collect()
            ->pluck('tld')
            ->unique()
            ->map(function ($item) {
                return '.'.$item;
            })
            ->values()
            ->all();

        $data = $this->request()
            ->get('billing/price/domain', [
                'name' => $tlds,
                'processes' => ['renew'],
            ])->throw()
            ->collect();

        $products = collect($data['products']);

        return $products->mapWithKeys(function ($item) {
            $tld = $item['name'];
            $price = $item['prices'][0]['price_after_taxes'];

            return [$tld => $price];
        });
    }

    public function getRenewalCostForTld(string $tld): float
    {
        $tld = '.'.ltrim($tld, '.');

        return $this->renewalCosts()->get($tld, 0.0);
    }
}
