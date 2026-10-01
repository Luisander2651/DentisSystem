<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * Spec 015 (CA16, CA17): trusts X-Forwarded-For and X-Forwarded-Proto only when the
 * connection comes from one of Cloudflare's ranges, so rate limits count real visitors and
 * a client that bypasses the proxy cannot pick its own IP.
 *
 * The ranges are read on every request: withMiddleware() in bootstrap/app.php runs before
 * the configuration is loaded, so config() cannot be called there.
 */
final class TrustCloudflareProxies extends TrustProxies
{
    /**
     * @return array<int, string>
     */
    protected function proxies(): array
    {
        return config('security.trusted_proxies', []);
    }

    protected function headers(): int
    {
        return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO;
    }
}
