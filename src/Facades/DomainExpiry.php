<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Facades;

use CranleighSchool\DomainExpiry\DomainExpiryService;
use CranleighSchool\DomainExpiry\WhoisResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static WhoisResult check(string $domain)
 * @method static Collection<int, WhoisResult> checkMany(array $domains)
 * @method static Collection<int, WhoisResult> expiringSoon(array $domains, ?int $withinDays = null)
 * @method static WhoisResult refresh(string $domain)
 * @method static Collection<int, WhoisResult> refreshMany(array $domains)
 * @method static array<string, int> summarise(Collection $results)
 * @method static array{critical: int, warning: int, notice: int} thresholds()
 *
 * @see DomainExpiryService
 */
class DomainExpiry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DomainExpiryService::class;
    }
}
