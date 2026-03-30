<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Http\Registrars;

use CranleighSchool\DomainExpiry\Exceptions\RegistrarImportException;
use CranleighSchool\DomainExpiry\RegistrarInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Gandi implements RegistrarInterface
{
    public function shouldTry(): bool
    {
        return ! empty(config('domain-expiry.registrars.gandi.auth.token'));
    }

    /**
     * @throws RegistrarImportException
     */
    public function getDomains(): Collection
    {
        try {
            return Http::withToken(config('domain-expiry.registrars.gandi.auth.token'))
                ->baseUrl('https://api.gandi.net/v5/')
                ->get('domain/domains')
                ->throw()
                ->collect()
                ->pluck('fqdn');
        } catch (RequestException|ConnectionException $e) {
            throw new RegistrarImportException('Gandi API request failed.', previous: $e);
        }
    }
}