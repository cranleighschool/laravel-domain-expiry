<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

final class WhoisResult
{
    public function __construct(
        public readonly string $domain,
        public readonly string $tld,
        public readonly string $server,
        public readonly string $rawResponse,
        public readonly ?\DateTimeImmutable $expiryDate,
        public readonly ?string $error,
    ) {}

    public static function error(string $domain, string $message): self
    {
        return new self(
            domain: $domain,
            tld: '',
            server: '',
            rawResponse: '',
            expiryDate: null,
            error: $message,
        );
    }

    public function isOk(): bool
    {
        return $this->error === null && $this->expiryDate !== null;
    }

    /**
     * Days until expiry. Negative = already expired.
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $diff = $now->diff($this->expiryDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Returns one of: ok | notice | warning | critical | expired | unknown
     */
    public function urgencyLevel(): UrgencyLevel
    {
        $days = $this->daysUntilExpiry();

        if ($days === null) {
            return UrgencyLevel::Unknown;
        }

        return match (true) {
            $days < 0 => UrgencyLevel::Expired,
            $days <= 14 => UrgencyLevel::Critical,
            $days <= 30 => UrgencyLevel::Warning,
            $days <= 60 => UrgencyLevel::Notice,
            default => UrgencyLevel::Ok,
        };
    }

    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'tld' => $this->tld,
            'server' => $this->server,
            'expiry_date' => $this->expiryDate?->format(\DateTimeInterface::ATOM),
            'days' => $this->daysUntilExpiry(),
            'urgency' => $this->urgencyLevel()->value,
            'error' => $this->error,
        ];
    }
}
