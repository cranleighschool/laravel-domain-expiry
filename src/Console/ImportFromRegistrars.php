<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Console;

use CranleighSchool\DomainExpiry\Exceptions\RegistrarImportException;
use CranleighSchool\DomainExpiry\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportFromRegistrars extends Command
{
    protected $signature = 'domain-expiry:registrar-import {registrar? : Optional registrar class name to import from (e.g. Gandi)}';

    protected $description = 'Gets all your Domains from Domain registrar(s) and updates/inserts them.';

    public function handle(): int
    {
        $only = $this->argument('registrar');

        foreach (app()->tagged('domain-expiry.registrars') as $api) {
            if ($only && class_basename($api) !== $only) {
                continue;
            }

            $this->alert('Trying '.class_basename($api));
            if (! $api->shouldTry()) {
                $this->line('Ignoring... does not meet config threshold.');
                continue;
            }
            try {
                $domains = $api->getDomains();
                $domains->each(fn (string $domain) => Domain::query()
                    ->updateOrCreate(
                        ['domain' => $domain],
                        [
                            'active' => true,
                            'notes' => class_basename($api),
                        ],
                    ));
                $this->info('Successfully imported '.count($domains).' domains from '.class_basename($api));
            } catch (RegistrarImportException $e) {
                Log::error('Failed to import domains from registrar: '.$e->getMessage(), ['exception' => $e]);
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
