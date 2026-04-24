<?php

return [
    'registrars' => [
        'gandi' => [
            'auth' => [
                'token' => env('GANDI_ORGANISATION_API_KEY', null),
            ],
        ],
        'porkbun' => [
            'auth' => [
                'apikey' => env('PORKBUN_API_KEY', null),
                'secretapikey' => env('PORKBUN_SECRET_API_KEY', null),
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route
    |--------------------------------------------------------------------------
    |
    | URI prefix and optional middleware for the built-in web dashboard.
    | Set 'enabled' to false to disable the dashboard entirely.
    |
    */

    'dashboard' => [
        'enabled' => true,
        'uri' => '/domain-expiry',
        'middleware' => ['web', 'auth'],  // e.g. ['web', 'auth']
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgency Thresholds (days)
    |--------------------------------------------------------------------------
    |
    | Domains with fewer days remaining than each threshold will be shown
    | at that urgency level. Adjust to suit your renewal lead times.
    |
    */

    'thresholds' => [
        'critical' => 14,
        'warning' => 30,
        'notice' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | WHOIS servers rate-limit heavily. Results are cached to avoid hammering
    | them on every page load. Set cache_store to null to use the default.
    | Set cache_ttl to 0 to disable caching (not recommended in production).
    |
    */

    'cache_store' => null,  // e.g. 'redis'
    'cache_ttl' => 7200,  // seconds (2 hours)

    /*
    |--------------------------------------------------------------------------
    | WHOIS Connection Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => 10,   // TCP socket timeout in seconds
    'polite_delay_ms' => 500,  // Delay between sequential queries (ms)

    /*
    |--------------------------------------------------------------------------
    | Extra / Override WHOIS Servers
    |--------------------------------------------------------------------------
    |
    | Add custom TLD→server mappings here. These are merged with (and can
    | override) the built-in server list.
    |
    | Example:
    |   'sch.uk' => 'whois.nic.uk',
    |
    */

    'extra_whois_servers' => [

    ],

];
