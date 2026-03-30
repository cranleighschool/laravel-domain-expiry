<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class DomainExpiryService
{
    public function __construct(
        private readonly WhoisChecker $checker,
        private readonly CacheRepository $cache,
        private readonly int $cacheTtl,
        private readonly array $thresholds,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Check a single domain, using the cache if available.
     */
    public function check(string $domain): WhoisResult
    {
        $key = $this->cacheKey($domain);

        return $this->cache->remember(
            key: $key,
            ttl: $this->cacheTtl,
            callback: fn () => $this->checker->check($domain),
        );
    }

    /**
     * Check multiple domains and return a Collection sorted by soonest expiry.
     *
     * @param  string[]  $domains
     * @return Collection<int, WhoisResult>
     */
    public function checkMany(array $domains): Collection
    {
        return collect($domains)
            ->map(fn (string $d) => $this->check($d))
            ->sortBy(fn (WhoisResult $r) => $r->daysUntilExpiry() ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Return only domains expiring within $withinDays days (or already expired).
     *
     * @param  string[]  $domains
     * @return Collection<int, WhoisResult>
     */
    public function expiringSoon(array $domains, ?int $withinDays = null): Collection
    {
        $threshold = $withinDays ?? $this->thresholds['notice'];

        return $this->checkMany($domains)
            ->filter(fn (WhoisResult $r) => ($r->daysUntilExpiry() ?? PHP_INT_MAX) <= $threshold);
    }

    /**
     * Force-refresh a single domain, bypassing the cache.
     */
    public function refresh(string $domain): WhoisResult
    {
        $this->cache->forget($this->cacheKey($domain));

        return $this->check($domain);
    }

    /**
     * Force-refresh all given domains.
     *
     * @param  string[]  $domains
     * @return Collection<int, WhoisResult>
     */
    public function refreshMany(array $domains): Collection
    {
        foreach ($domains as $domain) {
            $this->cache->forget($this->cacheKey($domain));
        }

        return $this->checkMany($domains);
    }

    /**
     * Get the configured urgency thresholds.
     *
     * @return array{critical: int, warning: int, notice: int}
     */
    public function thresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Summarise a collection of results by urgency level.
     *
     * @param  Collection<int, WhoisResult>  $results
     * @return array<string, int>
     */
    public function summarise(Collection $results): array
    {
        $counts = array_fill_keys(
            array_column(UrgencyLevel::cases(), 'value'),
            0,
        );

        foreach ($results as $result) {
            $counts[$result->urgencyLevel()->value]++;
        }

        return $counts;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function cacheKey(string $domain): string
    {
        return 'domain_expiry.'.md5(strtolower(trim($domain)));
    }
}
