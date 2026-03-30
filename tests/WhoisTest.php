<?php

declare(strict_types=1);

use CranleighSchool\DomainExpiry\UrgencyLevel;
use CranleighSchool\DomainExpiry\WhoisChecker;
use CranleighSchool\DomainExpiry\WhoisResult;

// ---------------------------------------------------------------------------
// WhoisResult
// ---------------------------------------------------------------------------

describe('WhoisResult', function () {
    it('calculates days until expiry correctly', function () {
        $future = new DateTimeImmutable('+45 days', new DateTimeZone('UTC'));
        $result = new WhoisResult(
            domain: 'example.com', tld: 'com', server: 'whois.example.com',
            rawResponse: '', expiryDate: $future, error: null,
        );

        expect($result->daysUntilExpiry())->toBeBetween(44, 46);
    });

    it('returns negative days for expired domains', function () {
        $past = new DateTimeImmutable('-5 days', new DateTimeZone('UTC'));
        $result = new WhoisResult(
            domain: 'example.com', tld: 'com', server: 'whois.example.com',
            rawResponse: '', expiryDate: $past, error: null,
        );

        expect($result->daysUntilExpiry())->toBeLessThan(0);
        expect($result->urgencyLevel())->toBe(UrgencyLevel::Expired);
    });

    it('returns null days when expiry date is unknown', function () {
        $result = WhoisResult::error('example.com', 'Could not parse');
        expect($result->daysUntilExpiry())->toBeNull();
        expect($result->urgencyLevel())->toBe(UrgencyLevel::Unknown);
        expect($result->isOk())->toBeFalse();
    });

    it('serialises to array correctly', function () {
        $expiry = new DateTimeImmutable('2026-12-31T00:00:00+00:00');
        $result = new WhoisResult(
            domain: 'example.com', tld: 'com', server: 'whois.verisign-grs.com',
            rawResponse: '', expiryDate: $expiry, error: null,
        );

        $arr = $result->toArray();

        expect($arr)->toHaveKeys(['domain', 'tld', 'server', 'expiry_date', 'days', 'urgency', 'error'])
            ->and($arr['domain'])->toBe('example.com')
            ->and($arr['tld'])->toBe('com');
    });
});

// ---------------------------------------------------------------------------
// UrgencyLevel
// ---------------------------------------------------------------------------

describe('UrgencyLevel', function () {
    it('maps day ranges to urgency levels', function () {
        $thresholds = ['critical' => 14, 'warning' => 30, 'notice' => 60];

        expect(UrgencyLevel::fromDays(null, $thresholds))->toBe(UrgencyLevel::Unknown);
        expect(UrgencyLevel::fromDays(-1, $thresholds))->toBe(UrgencyLevel::Expired);
        expect(UrgencyLevel::fromDays(0, $thresholds))->toBe(UrgencyLevel::Critical);
        expect(UrgencyLevel::fromDays(14, $thresholds))->toBe(UrgencyLevel::Critical);
        expect(UrgencyLevel::fromDays(15, $thresholds))->toBe(UrgencyLevel::Warning);
        expect(UrgencyLevel::fromDays(30, $thresholds))->toBe(UrgencyLevel::Warning);
        expect(UrgencyLevel::fromDays(31, $thresholds))->toBe(UrgencyLevel::Notice);
        expect(UrgencyLevel::fromDays(60, $thresholds))->toBe(UrgencyLevel::Notice);
        expect(UrgencyLevel::fromDays(61, $thresholds))->toBe(UrgencyLevel::Ok);
        expect(UrgencyLevel::fromDays(365, $thresholds))->toBe(UrgencyLevel::Ok);
    });
});

// ---------------------------------------------------------------------------
// WhoisChecker (unit — no real network calls)
// ---------------------------------------------------------------------------

describe('WhoisChecker TLD extraction', function () {
    it('resolves two-part TLDs correctly', function () {
        $checker = new WhoisChecker;

        // We test server resolution indirectly via hasServerFor()
        expect($checker->hasServerFor('co.uk'))->toBeTrue();
        expect($checker->hasServerFor('com.au'))->toBeTrue();
        expect($checker->hasServerFor('co.nz'))->toBeTrue();
    });

    it('has servers for common TLDs', function () {
        $checker = new WhoisChecker;

        foreach (['com', 'net', 'org', 'io', 'uk', 'de', 'fr', 'app', 'ai', 'dev'] as $tld) {
            expect($checker->hasServerFor($tld))->toBeTrue("Missing server for .{$tld}");
        }
    });

    it('accepts extra server overrides', function () {
        $checker = new WhoisChecker(extraServers: ['sch.uk' => 'whois.nic.uk']);
        expect($checker->hasServerFor('sch.uk'))->toBeTrue();
    });
});
