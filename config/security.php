<?php

/*
|--------------------------------------------------------------------------
| HTTP security (spec 015)
|--------------------------------------------------------------------------
|
| Content-Security-Policy sources used by App\Core\Middlewares\SecurityHeaders.
| 'self' is always allowed; these are the only third-party origins the pages load.
|
*/

$r2Url = parse_url((string) env('R2_URL', ''));
$r2Origin = isset($r2Url['scheme'], $r2Url['host'])
    ? $r2Url['scheme'].'://'.$r2Url['host'].(isset($r2Url['port']) ? ':'.$r2Url['port'] : '')
    : null;

return [

    'csp' => [
        // Emergency switch: send the policy as Report-Only so nothing is blocked.
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        'sources' => [
            'img' => array_values(array_filter([$r2Origin])),
            'style' => ['https://fonts.bunny.net'],
            'font' => ['https://fonts.bunny.net'],
            'frame' => ['https://www.google.com'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies (spec 015, CA16 and CA17)
    |--------------------------------------------------------------------------
    |
    | Cloudflare's published ranges (https://www.cloudflare.com/ips/, 2026-09-29).
    | Forwarded client IPs are accepted only from these addresses. docker/nginx/prod.conf
    | lists the same ranges for real_ip; CloudflareRangesTest keeps both lists equal.
    |
    */

    'trusted_proxies' => [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],

];
