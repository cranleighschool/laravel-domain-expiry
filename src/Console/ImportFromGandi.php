<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Console;

use CranleighSchool\DomainExpiry\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ImportFromGandi extends Command
{
    protected $signature = 'domain-expiry:gandi-import';

    protected $description = 'Gets all your Domains from your Gandi Organisation and updates/inserts them. ';

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $token = config('domain-expiry.services.gandi');
        if (empty($token)) {
            $this->error('Gandi Organisation API key is required.');

            return self::FAILURE;
        }

        Http::withToken($token)
            ->baseUrl('https://api.gandi.net/v5/')
            ->get('domain/domains')
            ->throw()
            ->collect()
            ->pluck('fqdn')
            ->each(fn (string $domain) => Domain::query()
                ->updateOrCreate(
                    ['domain' => $domain],
                    [
                        'active' => true,
                        'notes' => 'Gandi',
                    ],
                ));

        return self::SUCCESS;
    }
}
