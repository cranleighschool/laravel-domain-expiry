<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Expiry Dashboard</title>
    @livewireStyles
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@300;400;600&family=IBM+Plex+Sans:wght@300;500&display=swap');

        :root {
            --bg:       #f5f7fa;
            --surface:  #ffffff;
            --border:   #dde1e9;
            --muted:    #8a94a6;
            --text:     #3a4255;
            --bright:   #1a202e;
            --ok:       #1a9e62;
            --notice:   #1a7bbf;
            --warning:  #b07d00;
            --critical: #c94a28;
            --expired:  #a01530;
            --unknown:  #6b7280;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13px;
            min-height: 100vh;
            padding: 2rem;
        }

        header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left { display: flex; align-items: baseline; gap: 1.5rem; }

        h1 {
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 500;
            font-size: 1.35rem;
            color: var(--bright);
            letter-spacing: 0.03em;
        }

        .subtitle { color: var(--muted); font-size: 11px; }

        .header-actions { display: flex; gap: 0.75rem; }

        .btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            padding: 5px 14px;
            cursor: pointer;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
            transition: border-color 0.15s, color 0.15s;
        }

        .btn:hover { border-color: var(--bright); color: var(--bright); }

        .btn-primary {
            border-color: var(--notice);
            color: var(--notice);
        }

        /* Flash message */
        .flash {
            background: var(--surface);
            border-left: 3px solid var(--ok);
            color: var(--ok);
            padding: 0.6rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 12px;
        }

        /* Summary strip */
        .summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .summary-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.65rem 1.1rem;
            min-width: 90px;
        }

        .summary-card .num {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1;
            margin-bottom: 0.2rem;
        }

        .summary-card .label {
            font-size: 9px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .c-ok       { color: var(--ok); }
        .c-notice   { color: var(--notice); }
        .c-warning  { color: var(--warning); }
        .c-critical { color: var(--critical); }
        .c-expired  { color: var(--expired); }
        .c-unknown  { color: var(--unknown); }

        /* Table */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            text-align: left;
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 400;
            padding: 0 1rem 0.6rem 0;
            border-bottom: 1px solid var(--border);
        }

        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
        tbody tr:hover { background: var(--surface); }
        tbody td { padding: 0.75rem 1rem 0.75rem 0; vertical-align: middle; }

        .domain-name { color: var(--bright); font-weight: 600; }

        .days-value { font-size: 1.15rem; font-weight: 600; }

        .expiry-date { color: var(--muted); font-size: 11px; margin-top: 2px; }

        .error-text { color: var(--muted); font-style: italic; font-size: 11px; }

        .badge {
            display: inline-block;
            font-size: 9px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 2px 7px;
            font-weight: 600;
            border: 1px solid;
        }

        .badge-ok       { color: var(--ok);       border-color: var(--ok); }
        .badge-notice   { color: var(--notice);   border-color: var(--notice); }
        .badge-warning  { color: var(--warning);  border-color: var(--warning); }
        .badge-critical { color: var(--critical); border-color: var(--critical); }
        .badge-expired  { color: var(--expired);  border-color: var(--expired); background: rgba(192,48,74,0.1); }
        .badge-unknown  { color: var(--unknown);  border-color: var(--unknown); }

        .bar-track { height: 3px; background: var(--border); width: 100px; margin-top: 5px; }
        .bar { height: 100%; }
        .bar-ok       { background: var(--ok); }
        .bar-notice   { background: var(--notice); }
        .bar-warning  { background: var(--warning); }
        .bar-critical { background: var(--critical); }
        .bar-expired  { background: var(--expired); }

        .refresh-form { display: inline; }

        .refresh-btn {
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 11px;
            font-family: 'IBM Plex Mono', monospace;
            padding: 0;
            letter-spacing: 0.04em;
        }

        .refresh-btn:hover { color: var(--text); }

        footer {
            margin-top: 3rem;
            color: var(--muted);
            font-size: 10px;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('vendor/domain-expiry/domain-expiry.css') }}" />
</head>
<body>

<header>
    <div class="header-left">
        <h1>Domain Expiry</h1>
        <span class="subtitle">{{ now()->format('Y-m-d H:i:s') }} UTC</span>
    </div>
    <div class="header-actions">
        <a href="{{ route('domain-expiry.json') }}" class="btn" target="_blank">JSON ↗</a>
        <form action="{{ route('domain-expiry.refresh-all') }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary">↻ Refresh All</button>
        </form>
    </div>
</header>

@if (session('refreshed'))
    <div class="flash">↻ Refreshed: {{ session('refreshed') }}</div>
@endif

@if (session('refreshed_all'))
    <div class="flash">↻ All domains refreshed from WHOIS</div>
@endif

{{-- Summary strip --}}
@php
    $levelOrder = ['expired', 'critical', 'warning', 'notice', 'ok', 'unknown'];
    $levelColors = [
        'ok' => 'ok', 'notice' => 'notice', 'warning' => 'warning',
        'critical' => 'critical', 'expired' => 'expired', 'unknown' => 'unknown',
    ];
@endphp

<div class="summary">
    @foreach ($levelOrder as $level)
        @if (($summary[$level] ?? 0) > 0)
            <div class="summary-card">
                <div class="num c-{{ $levelColors[$level] }}">{{ $summary[$level] }}</div>
                <div class="label">{{ ucfirst($level) }}</div>
            </div>
        @endif
    @endforeach
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Status</th>
                <th>Days Remaining</th>
                <th>Expiry Date</th>
                <th>WHOIS Server</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $result)
                @php
                    $days  = $result->daysUntilExpiry();
                    $level = $result->urgencyLevel()->value;
                    $pct   = $days !== null ? min(100, max(0, (int) ($days / 365 * 100))) : 0;
                @endphp
                <tr>
                    <td class="domain-name">{{ $result->domain }}</td>
                    <td><span class="badge badge-{{ $level }}">{{ strtoupper($level) }}</span></td>
                    <td>
                        @if ($days !== null)
                            <span class="days-value c-{{ $level }}">{{ $days }}</span>
                            <div class="bar-track">
                                <div class="bar bar-{{ $level }}" style="width:{{ $pct }}%"></div>
                            </div>
                        @else
                            <span class="error-text">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($result->expiryDate)
                            <div>{{ $result->expiryDate->format('d M Y') }}</div>
                            <div class="expiry-date">{{ $result->expiryDate->format('H:i:s') }} UTC</div>
                        @elseif ($result->error)
                            <span class="error-text">{{ $result->error }}</span>
                        @endif
                    </td>
                    <td class="expiry-date">{{ $result->server ?: '—' }}</td>
                    <td>
                        <form action="{{ route('domain-expiry.refresh') }}" method="POST" class="refresh-form">
                            @csrf
                            <input type="hidden" name="domain" value="{{ $result->domain }}">
                            <button type="submit" class="refresh-btn">↻ refresh</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<footer>
    <span>{{ $results->count() }} domain(s) checked</span>
    <span>cache TTL {{ config('domain-expiry.cache_ttl') }}s &bull; data from public WHOIS servers</span>
</footer>

<livewire:domain-expiry.domain-manager />

@livewireScripts
</body>
</html>
