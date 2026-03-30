<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Http\Registrars;

use CranleighSchool\DomainExpiry\Exceptions\RegistrarImportException;
use CranleighSchool\DomainExpiry\RegistrarInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Porkbun implements RegistrarInterface
{
    public function shouldTry(): bool
    {
        return ! empty(config('domain-expiry.registrars.porkbun.auth.apikey'))
            && ! empty(config('domain-expiry.registrars.porkbun.auth.secretapikey'));
    }

    /**
     * @throws RegistrarImportException
     */
    public function getDomains(): Collection
    {
        try {
            $response = Http::baseUrl('https://api.porkbun.com/api/json/v3')
                ->post('domain/listAll', [
                    'secretapikey' => config('domain-expiry.registrars.porkbun.auth.secretapikey'),
                    'apikey' => config('domain-expiry.registrars.porkbun.auth.apikey'),
                ])->throw()
                ->object();
        } catch (RequestException|ConnectionException $e) {
            throw new RegistrarImportException('Porkbun API request failed', previous: $e);
        }

        if ($response->status === 'SUCCESS' && isset($response->domains)) {
            return collect($response->domains)->pluck('domain');
        }

        throw new RegistrarImportException('Porkbun API returned an unexpected response');
    }
}