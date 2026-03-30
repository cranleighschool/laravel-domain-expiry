<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

use CranleighSchool\DomainExpiry\Http\Registrars\Gandi;
use CranleighSchool\DomainExpiry\Http\Registrars\Porkbun;
use CranleighSchool\DomainExpiry\Livewire\DomainManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DomainExpiryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/domain-expiry.php',
            key: 'domain-expiry',
        );

        $this->app->tag([Gandi::class, Porkbun::class], 'domain-expiry.registrars');

        $this->app->singleton(WhoisChecker::class, function () {
            $config = config('domain-expiry');

            return new WhoisChecker(
                extraServers: $config['extra_whois_servers'] ?? [],
                timeout: $config['timeout'] ?? 10,
                politeDelayMs: $config['polite_delay_ms'] ?? 500,
            );
        });

        $this->app->singleton(DomainExpiryService::class, function ($app) {
            $config = config('domain-expiry');

            return new DomainExpiryService(
                checker: $app->make(WhoisChecker::class),
                cache: $app->make('cache')->store($config['cache_store'] ?? null),
                cacheTtl: $config['cache_ttl'] ?? 3600,
                thresholds: $config['thresholds'],
            );
        });
    }

    public function boot(): void
    {
        $this->commands([
            Console\CheckDomainsCommand::class,
            Console\ImportFromRegistrars::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/domain-expiry.php' => config_path('domain-expiry.php'),
            ], 'domain-expiry-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/domain-expiry'),
            ], 'domain-expiry-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'domain-expiry-migrations');

            $this->publishes([
                __DIR__.'/../resources/css/domain-expiry.css' => public_path('vendor/domain-expiry/domain-expiry.css'),
            ], 'domain-expiry-css');
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('domain-expiry:registrar-import')->daily();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'domain-expiry');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Livewire::component('domain-expiry.domain-manager', DomainManager::class);
    }
}
