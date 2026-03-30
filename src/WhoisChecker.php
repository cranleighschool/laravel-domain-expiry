<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

use Spatie\Rdap\Facades\Rdap;

class WhoisChecker
{
    /**
     * TLD => WHOIS server hostname.
     * Two-part TLDs (co.uk) must appear before their single-part suffix (uk).
     *
     * @var array<string, string>
     */
    private const array DEFAULT_SERVERS = [
        // Two-part TLDs first
        'co.uk' => 'whois.nic.uk',
        'org.uk' => 'whois.nic.uk',
        'me.uk' => 'whois.nic.uk',
        'net.uk' => 'whois.nic.uk',
        'com.au' => 'whois.auda.org.au',
        'net.au' => 'whois.auda.org.au',
        'org.au' => 'whois.auda.org.au',
        'co.nz' => 'whois.nic.nz',
        'net.nz' => 'whois.nic.nz',
        'org.nz' => 'whois.nic.nz',
        'co.jp' => 'whois.jprs.jp',

        // Generic / popular
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'edu' => 'whois.educause.edu',
        'gov' => 'whois.dotgov.gov',
        'mil' => 'whois.nic.mil',
        'int' => 'whois.iana.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'name' => 'whois.nic.name',
        'mobi' => 'whois.dotmobiregistry.net',
        'tel' => 'whois.nic.tel',
        'travel' => 'whois.nic.travel',
        'museum' => 'whois.museum',
        'coop' => 'whois.nic.coop',

        // Country codes
        'uk' => 'whois.nic.uk',
        'us' => 'whois.nic.us',
        'ca' => 'whois.cira.ca',
        'au' => 'whois.auda.org.au',
        'nz' => 'whois.nic.nz',
        'de' => 'whois.denic.de',
        'fr' => 'whois.nic.fr',
        'nl' => 'whois.domain-registry.nl',
        'eu' => 'whois.eu',
        'it' => 'whois.nic.it',
        'es' => 'whois.nic.es',
        'pl' => 'whois.dns.pl',
        'se' => 'whois.iis.se',
        'no' => 'whois.norid.no',
        'dk' => 'whois.dk-hostmaster.dk',
        'fi' => 'whois.fi',
        'be' => 'whois.dns.be',
        'ch' => 'whois.nic.ch',
        'at' => 'whois.nic.at',
        'jp' => 'whois.jprs.jp',
        'cn' => 'whois.cnnic.cn',
        'ru' => 'whois.tcinet.ru',
        'br' => 'whois.registro.br',
        'mx' => 'whois.mx',
        'in' => 'whois.registry.in',
        'sg' => 'whois.sgnic.sg',
        'hk' => 'whois.hkirc.hk',
        'za' => 'whois.registry.net.za',
        'ie' => 'whois.iedr.ie',
        'io' => 'whois.nic.io',
        'co' => 'whois.nic.co',
        'me' => 'whois.nic.me',
        'tv' => 'tvwhois.verisign-grs.com',
        'cc' => 'ccwhois.verisign-grs.com',

        // New gTLDs
        'app' => 'whois.nic.google',
        'dev' => 'whois.nic.google',
        'page' => 'whois.nic.google',
        'new' => 'whois.nic.google',
        'xyz' => 'whois.nic.xyz',
        'online' => 'whois.nic.online',
        'shop' => 'whois.nic.shop',
        'tech' => 'whois.nic.tech',
        'ai' => 'whois.nic.ai',
        'club' => 'whois.nic.club',
        'live' => 'whois.nic.live',
        'store' => 'whois.nic.store',
        'site' => 'whois.nic.site',
        'website' => 'whois.nic.website',
        'email' => 'whois.nic.email',
        'blog' => 'whois.nic.blog',
        'media' => 'whois.nic.media',
        'news' => 'whois.nic.news',
        'cloud' => 'whois.nic.cloud',
        'digital' => 'whois.nic.digital',
        'agency' => 'whois.nic.agency',
        'studio' => 'whois.nic.studio',
        'design' => 'whois.nic.design',
        'link' => 'whois.uniregistry.net',
        'click' => 'whois.uniregistry.net',
        'help' => 'whois.uniregistry.net',
        'global' => 'whois.nic.global',
        'world' => 'whois.nic.world',
        'today' => 'whois.nic.today',
    ];

    /**
     * Expiry line patterns — tried in order, first match wins.
     * Covers Verisign, PIR, Nominet, DENIC, AFNIC, AUDA, etc.
     */
    private const EXPIRY_PATTERNS = [
        '/Registry Expiry Date:\s*(.+)/i',
        '/Registrar Registration Expiration Date:\s*(.+)/i',
        '/Expiry Date:\s*(.+)/i',
        '/Expiration Date:\s*(.+)/i',
        '/Expiration Time:\s*(.+)/i',
        '/Domain Expiration Date:\s*(.+)/i',
        '/expire:\s*(.+)/i',
        '/expires:\s*(.+)/i',
        '/Expires:\s*(.+)/i',
        '/Expires On:\s*(.+)/i',
        '/Expiry:\s*(.+)/i',
        '/paid-till:\s*(.+)/i',
        '/valid-date:\s*(.+)/i',
        '/renewal date:\s*(.+)/i',
        '/renewal:\s*(.+)/i',
        '/Expiration date:\s*(.+)/i',
        '/nserver.*expir.*:\s*(.+)/i',
    ];

    /** @var array<string, string> */
    private array $servers;

    private int $timeout;

    private int $politeDelayMs;

    /**
     * @param  array<string, string>|null  $extraServers  Additional/override TLD→server mappings
     * @param  int  $timeout  TCP socket timeout in seconds
     * @param  int  $politeDelayMs  Milliseconds to sleep between queries
     */
    public function __construct(
        ?array $extraServers = null,
        int $timeout = 10,
        int $politeDelayMs = 500,
    ) {
        $this->servers = array_merge(self::DEFAULT_SERVERS, $extraServers ?? []);
        $this->timeout = $timeout;
        $this->politeDelayMs = $politeDelayMs;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public function check(string $domain): WhoisResult
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./i', '', $domain);

        $tld = $this->extractTld($domain);

        // Try RDAP first — it's the modern, structured protocol
        try {
            $rdap = Rdap::domain($domain);

            if ($rdap !== null) {
                $expirationDate = $rdap->expirationDate();

                if ($expirationDate !== null) {
                    return new WhoisResult(
                        domain: $domain,
                        tld: $tld,
                        server: Rdap::dns()->getServerForDomain($domain) ?? 'RDAP',
                        rawResponse: '',
                        expiryDate: $expirationDate->toDateTimeImmutable(),
                        error: null,
                    );
                }
            }
        } catch (\Throwable) {
            // RDAP unsupported or failed — fall through to WHOIS
        }

        // Fall back to WHOIS (e.g. .me and other TLDs without RDAP support)
        $server = $this->servers[$tld] ?? null;

        if ($server === null) {
            return WhoisResult::error($domain, 'No RDAP support and no WHOIS server configured for this TLD.');
        }

        $raw = $this->queryServer($domain, $server);

        if ($raw === null) {
            return WhoisResult::error($domain, "Failed to connect to WHOIS server: {$server}");
        }

        $expiry = $this->parseExpiry($raw);

        return new WhoisResult(
            domain: $domain,
            tld: $tld,
            server: $server,
            rawResponse: $raw,
            expiryDate: $expiry,
            error: $expiry === null ? 'Expiry date not found in WHOIS response' : null,
        );
    }

    /**
     * @param  string[]  $domains
     * @return WhoisResult[]
     */
    public function checkMany(array $domains): array
    {
        $results = [];

        foreach ($domains as $i => $domain) {
            if ($i > 0) {
                usleep($this->politeDelayMs * 1_000);
            }
            $results[] = $this->check($domain);
        }

        return $results;
    }

    public function hasServerFor(string $tld): bool
    {
        return isset($this->servers[$tld]);
    }

    /** @return array<string, string> */
    public function servers(): array
    {
        return $this->servers;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        $count = count($parts);

        // Try two-part TLD (e.g. co.uk, com.au)
        if ($count >= 3) {
            $candidate = $parts[$count - 2].'.'.$parts[$count - 1];
            if (isset($this->servers[$candidate])) {
                return $candidate;
            }
        }

        return $parts[$count - 1];
    }

    private function queryServer(string $domain, string $server): ?string
    {
        $fp = @fsockopen($server, 43, $errno, $errstr, $this->timeout);

        if ($fp === false) {
            return null;
        }

        stream_set_timeout($fp, $this->timeout);

        // DENIC requires "-T dn,ace " prefix
        $query = str_ends_with($server, 'denic.de')
            ? "-T dn,ace {$domain}\r\n"
            : "{$domain}\r\n";

        fwrite($fp, $query);

        $response = '';
        while (! feof($fp)) {
            $chunk = fgets($fp, 4096);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }

        fclose($fp);

        return $response !== '' ? $response : null;
    }

    /**
     * Walk each known expiry-line pattern until one matches, then attempt to
     * parse the captured date string twice: once as-is, and once with the
     * trailing token stripped (e.g. "2027-03-15 UTC" → "2027-03-15"), since
     * some WHOIS servers append a timezone label that DateTimeImmutable cannot
     * parse natively.
     */
    private function parseExpiry(string $raw): ?\DateTimeImmutable
    {
        foreach (self::EXPIRY_PATTERNS as $pattern) {
            if (preg_match($pattern, $raw, $matches)) {
                $dateStr = trim($matches[1]);

                // Some servers return multiple lines; take the first
                $dateStr = explode("\n", $dateStr)[0];
                $dateStr = trim($dateStr);

                // Attempt parse with and without trailing tokens (e.g. "UTC", "GMT")
                foreach ([$dateStr, preg_replace('/\s+\S+$/', '', $dateStr)] as $attempt) {
                    if (empty($attempt)) {
                        continue;
                    }

                    try {
                        $dt = new \DateTimeImmutable($attempt, new \DateTimeZone('UTC'));
                        // Sanity: must be a plausible domain expiry year
                        if ($dt->format('Y') >= 2000) {
                            return $dt;
                        }
                    } catch (\Exception) {
                        // Try next attempt
                    }
                }
            }
        }

        return null;
    }
}
