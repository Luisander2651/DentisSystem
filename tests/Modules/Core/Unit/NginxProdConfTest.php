<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, /review R7 and R8 (CA5, CA16, CA17, TM4, TM14): what nginx itself answers or
 * forwards in production. The configuration is parsed into blocks, without comments, so a
 * directive in the wrong block or in a comment does not satisfy the test.
 */

/**
 * The blocks declared directly inside $body, as [header, body] pairs.
 *
 * @return list<array{0: string, 1: string}>
 */
function nginxBlocks(string $body): array
{
    $blocks = [];
    $depth = 0;
    $start = 0;
    $headerStart = 0;
    $length = strlen($body);

    for ($i = 0; $i < $length; $i++) {
        $character = $body[$i];
        if ($character === ';' && $depth === 0) {
            $headerStart = $i + 1;
        } elseif ($character === '{') {
            if ($depth === 0) {
                $header = trim(substr($body, $headerStart, $i - $headerStart));
                $start = $i + 1;
            }
            $depth++;
        } elseif ($character === '}') {
            $depth--;
            if ($depth === 0) {
                $blocks[] = [$header, substr($body, $start, $i - $start)];
                $headerStart = $i + 1;
            }
        }
    }

    return $blocks;
}

/**
 * The directives declared directly in $body, outside any nested block.
 */
function nginxOwnDirectives(string $body): string
{
    foreach (nginxBlocks($body) as [, $inner]) {
        $body = str_replace('{'.$inner.'}', '', $body);
    }

    return $body;
}

/**
 * @return array{0: string, 1: list<array{0: string, 1: string}>} own directives and locations of the 443 server
 */
function nginxTlsServer(): array
{
    $configuration = (string) file_get_contents(base_path('docker/nginx/prod.conf'));
    $configuration = (string) preg_replace('/#.*$/m', '', $configuration);

    foreach (nginxBlocks($configuration) as [$header, $body]) {
        if ($header === 'server' && preg_match('/\blisten\s+443\b/', nginxOwnDirectives($body))) {
            return [nginxOwnDirectives($body), nginxBlocks($body)];
        }
    }

    throw new RuntimeException('docker/nginx/prod.conf has no server listening on 443');
}

const NGINX_PROTECTIVE_HEADERS = [
    'Strict-Transport-Security' => '/add_header\s+Strict-Transport-Security\s+"max-age=\d+[^"]*"\s+always\s*;/',
    'X-Content-Type-Options' => '/add_header\s+X-Content-Type-Options\s+nosniff\s+always\s*;/',
    'X-Frame-Options' => '/add_header\s+X-Frame-Options\s+DENY\s+always\s*;/',
];

it('sends the protective headers with every file nginx serves by itself (R8)', function () {
    [$server] = nginxTlsServer();

    foreach (NGINX_PROTECTIVE_HEADERS as $name => $pattern) {
        expect($server)->toMatch($pattern, "{$name} is not set at the level of the 443 server");
    }
});

it('repeats the protective headers in every location that declares its own (R8)', function () {
    // nginx drops the inherited add_header directives as soon as a block declares one.
    [, $locations] = nginxTlsServer();
    $withOwnHeaders = array_filter($locations, fn (array $location): bool => str_contains($location[1], 'add_header'));

    expect($locations)->not->toBeEmpty();
    foreach ($withOwnHeaders as [$header, $body]) {
        foreach (NGINX_PROTECTIVE_HEADERS as $name => $pattern) {
            expect($body)->toMatch($pattern, "{$header} declares headers but not {$name}");
        }
    }
});

it('hands PHP the client IP nginx resolved, not the X-Forwarded-For the client sent (R7)', function () {
    [, $locations] = nginxTlsServer();
    $php = array_values(array_filter($locations, fn (array $location): bool => str_contains($location[1], 'fastcgi_pass')));

    expect($php)->toHaveCount(1);

    // After the include, so it replaces the header instead of being replaced by it.
    expect($php[0][1])->toMatch('/include\s+fastcgi_params\s*;.*fastcgi_param\s+HTTP_X_FORWARDED_FOR\s+\$remote_addr\s*;/s');
});

it('does not send the headers twice on PHP responses (R8)', function () {
    // Laravel's SecurityHeaders middleware sets them too; nginx hides those and adds its own,
    // which also cover the errors nginx answers when PHP is down.
    [, $locations] = nginxTlsServer();
    $php = array_values(array_filter($locations, fn (array $location): bool => str_contains($location[1], 'fastcgi_pass')))[0][1];

    expect($php)->toMatch('/fastcgi_hide_header\s+X-Content-Type-Options\s*;/')
        ->and($php)->toMatch('/fastcgi_hide_header\s+X-Frame-Options\s*;/')
        ->and($php)->not->toContain('add_header');
});
