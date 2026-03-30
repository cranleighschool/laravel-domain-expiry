<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Console;

use CranleighSchool\DomainExpiry\DomainExpiryService;
use CranleighSchool\DomainExpiry\Models\Domain;
use CranleighSchool\DomainExpiry\UrgencyLevel;
use CranleighSchool\DomainExpiry\WhoisResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckDomainsCommand extends Command
{
    protected $signature = 'domain-expiry:check
                            {--domain=* : Specific domain(s) to check (overrides config)}
                            {--json      : Output results as JSON}
                            {--warn-only : Only show domains that are not OK}
                            {--refresh   : Bypass cache and force fresh WHOIS queries}';

    protected $description = 'Check WHOIS expiry for all configured domains';

    public function handle(DomainExpiryService $service): int
    {
        $domains = $this->option('domain') ?: Domain::query()->active()->pluck('domain')->all();

        if (empty($domains)) {
            $this->error('No domains configured. Add them via the dashboard or pass --domain=example.com');

            return self::FAILURE;
        }

        $this->info(sprintf('Checking %d domain(s)…', count($domains)));

        /** @var Collection<int, WhoisResult> $results */
        $results = $this->option('refresh')
            ? $service->refreshMany($domains)
            : $service->checkMany($domains);

        if ($this->option('warn-only')) {
            $results = $results->filter(
                fn (WhoisResult $r) => $r->urgencyLevel() !== UrgencyLevel::Ok,
            )->values();
        }

        if ($this->option('json')) {
            $this->line(json_encode($results->map->toArray()->all(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->renderTable($results);
        $this->renderSummary($service->summarise($results));

        // Exit with a non-zero code if any domains are critical or expired
        $hasUrgent = $results->contains(
            fn (WhoisResult $r) => in_array(
                $r->urgencyLevel(),
                [UrgencyLevel::Critical, UrgencyLevel::Expired],
                strict: true,
            ),
        );

        return $hasUrgent ? self::FAILURE : self::SUCCESS;
    }

    /** @param Collection<int, WhoisResult> $results */
    private function renderTable(Collection $results): void
    {
        $rows = $results->map(function (WhoisResult $r): array {
            $days = $r->daysUntilExpiry();
            $level = $r->urgencyLevel();

            $daysDisplay = match (true) {
                $days === null => '—',
                $days < 0 => "EXPIRED ({$days}d)",
                default => "{$days}d",
            };

            $expiry = $r->expiryDate?->format('Y-m-d') ?? '—';

            $status = match ($level) {
                UrgencyLevel::Ok => "<fg=green>{$level->label()}</>",
                UrgencyLevel::Notice => "<fg=cyan>{$level->label()}</>",
                UrgencyLevel::Warning => "<fg=yellow>{$level->label()}</>",
                UrgencyLevel::Critical => "<fg=red;options=bold>{$level->label()}</>",
                UrgencyLevel::Expired => "<fg=red;options=bold,underscore>{$level->label()}</>",
                UrgencyLevel::Unknown => "<fg=gray>{$level->label()}</>",
            };

            return [
                $r->domain,
                $status,
                $daysDisplay,
                $expiry,
                $r->server ?: '—',
            ];
        })->all();

        $this->table(
            headers: ['Domain', 'Status', 'Days Left', 'Expiry Date', 'WHOIS Server'],
            rows: $rows,
        );
    }

    /** @param array<string, int> $summary */
    private function renderSummary(array $summary): void
    {
        $parts = [];

        foreach ($summary as $level => $count) {
            if ($count === 0) {
                continue;
            }

            $parts[] = match ($level) {
                'ok' => "<fg=green>{$count} ok</>",
                'notice' => "<fg=cyan>{$count} notice</>",
                'warning' => "<fg=yellow>{$count} warning</>",
                'critical' => "<fg=red;options=bold>{$count} critical</>",
                'expired' => "<fg=red;options=bold,underscore>{$count} expired</>",
                default => "<fg=gray>{$count} unknown</>",
            };
        }

        $this->newLine();
        $this->line('Summary: '.implode('  |  ', $parts));
    }
}
