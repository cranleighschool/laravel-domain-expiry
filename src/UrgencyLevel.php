<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

enum UrgencyLevel: string
{
    case Ok = 'ok';
    case Notice = 'notice';
    case Warning = 'warning';
    case Critical = 'critical';
    case Expired = 'expired';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Notice => 'NOTICE',
            self::Warning => 'WARNING',
            self::Critical => 'CRITICAL',
            self::Expired => 'EXPIRED',
            self::Unknown => 'UNKNOWN',
        };
    }

    public function tailwindColor(): string
    {
        return match ($this) {
            self::Ok => 'green',
            self::Notice => 'blue',
            self::Warning => 'yellow',
            self::Critical => 'orange',
            self::Expired => 'red',
            self::Unknown => 'gray',
        };
    }

    /**
     * Thresholds are configurable — but the enum drives the order.
     * Pass custom thresholds from config if needed.
     *
     * @param  array{critical: int, warning: int, notice: int}  $thresholds
     */
    public static function fromDays(?int $days, array $thresholds): self
    {
        if ($days === null) {
            return self::Unknown;
        }

        return match (true) {
            $days < 0 => self::Expired,
            $days <= $thresholds['critical'] => self::Critical,
            $days <= $thresholds['warning'] => self::Warning,
            $days <= $thresholds['notice'] => self::Notice,
            default => self::Ok,
        };
    }
}
