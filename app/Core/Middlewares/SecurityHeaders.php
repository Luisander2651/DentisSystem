<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 015 (CA5, RS6.a): security headers on every response. The CSP only lets in the
 * site's own resources, inline scripts carrying this request's nonce, and the few
 * third-party origins listed in config/security.php. While vite runs hot, its dev server
 * and websocket are allowed too, so local development keeps hot reload (CA1).
 * HSTS is sent by nginx, which terminates TLS.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $header = config('security.csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, $this->policy($nonce));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    private function policy(string $nonce): string
    {
        /** @var array<string, list<string>> $sources */
        $sources = config('security.csp.sources', []);
        $devServer = $this->viteDevServerOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$devServer],
            'style-src' => ["'self'", "'unsafe-inline'", ...($sources['style'] ?? []), ...$devServer],
            'font-src' => ["'self'", ...($sources['font'] ?? [])],
            'img-src' => ["'self'", 'data:', ...($sources['img'] ?? [])],
            'connect-src' => ["'self'", ...$devServer],
            'frame-src' => $sources['frame'] ?? ["'none'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        return implode('; ', array_map(
            fn (string $directive, array $values): string => $directive.' '.implode(' ', array_unique($values)),
            array_keys($directives),
            $directives,
        ));
    }

    /**
     * @return list<string> the vite dev server and its websocket, only while running hot
     */
    private function viteDevServerOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        $url = trim((string) file_get_contents(Vite::hotFile()));
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        $hostAndPort = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $websocket = $parts['scheme'] === 'https' ? 'wss' : 'ws';

        return ["{$parts['scheme']}://{$hostAndPort}", "{$websocket}://{$hostAndPort}"];
    }
}
