<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, CA1, CA2, CA9 and CA10 (RD2.a, RD6.a, RD6.b, OB6.a, TM1, TM7, TM10, TM13): the
 * local and production Compose files, the Dockerfile and the .dockerignore keep their promises.
 */

/**
 * @return array<string, mixed>
 */
function composeFile(string $name): array
{
    $path = base_path($name);
    expect(is_file($path))->toBeTrue("{$name} does not exist");

    return Yaml::parseFile($path);
}

/**
 * @return list<string>
 */
function publishedPorts(array $compose): array
{
    $ports = [];
    foreach ($compose['services'] ?? [] as $service) {
        foreach ($service['ports'] ?? [] as $port) {
            $ports[] = (string) $port;
        }
    }
    sort($ports);

    return $ports;
}

function isInterpolated(string $value): bool
{
    return (bool) preg_match('/^\$\{[A-Z0-9_]+(:\?[^}]*)?\}$/', $value);
}

it('keeps every credential out of both Compose files (CA10)', function (string $file) {
    foreach (composeFile($file)['services'] ?? [] as $serviceName => $service) {
        foreach ($service['environment'] ?? [] as $key => $value) {
            if (is_int($key)) {
                [$key, $value] = array_pad(explode('=', (string) $value, 2), 2, '');
            }
            if (preg_match('/PASSWORD|SECRET|TOKEN|POSTGRES_(USER|DB)/', (string) $key)) {
                expect(isInterpolated((string) $value))->toBeTrue("{$file}: {$serviceName}.{$key} is not read from the environment");
            }
        }

        $command = is_array($service['command'] ?? null) ? implode(' ', $service['command']) : (string) ($service['command'] ?? '');
        if (str_contains($command, 'requirepass')) {
            expect($command)->toMatch('/requirepass\s+\$\{[A-Z0-9_]+(:\?[^}]*)?\}/', "{$file}: {$serviceName} has a literal Redis password");
        }
    }
})->with(['docker-compose.yml', 'docker-compose.prod.yml']);

it('publishes only 80, 443 and a local-only Grafana in production (CA9)', function () {
    expect(publishedPorts(composeFile('docker-compose.prod.yml')))
        ->toBe(['127.0.0.1:3000:3000', '443:443', '80:80']);
});

it('runs production as its own project, apart from the manual stack (D18)', function () {
    $compose = composeFile('docker-compose.prod.yml');

    expect($compose['name'] ?? null)->toBe('dentissa');
    foreach ($compose['services'] as $serviceName => $service) {
        expect((string) ($service['container_name'] ?? ''))->toStartWith('dentissa-', "{$serviceName} container name");
    }
    foreach (['db-data', 'redis-data', 'loki-data', 'grafana-data'] as $volume) {
        expect($compose['volumes'][$volume]['external'] ?? false)->toBeTrue("{$volume} is not external")
            ->and((string) ($compose['volumes'][$volume]['name'] ?? ''))->toContain('${DATA_VOLUME_PREFIX:?');
    }
});

it('serves the local stack on 8000 with vite on the loopback and without TLS (CA1)', function () {
    $ports = publishedPorts(composeFile('docker-compose.yml'));

    expect($ports)->toContain('8000:80')
        ->and($ports)->toContain('127.0.0.1:5173:5173')
        ->and(implode(' ', $ports))->not->toContain('443');
});

it('lets the local queue share vendor and node_modules with the app (A3)', function () {
    $services = composeFile('docker-compose.yml')['services'];
    $shared = fn (array $service): array => array_values(array_filter(
        $service['volumes'] ?? [],
        fn ($volume): bool => is_string($volume) && preg_match('#^[a-z0-9_-]+:/var/www/html/(vendor|node_modules)$#', $volume) === 1,
    ));

    expect($shared($services['app']))->toHaveCount(2)
        ->and($shared($services['queue']))->toBe($shared($services['app']));
});

it('pins every base image of the Dockerfile and uses the same Node as CI (CA2, A50)', function () {
    $dockerfile = (string) file_get_contents(base_path('docker/Dockerfile'));

    preg_match_all('/^FROM\s+(\S+)(?:\s+AS\s+(\S+))?/mi', $dockerfile, $from);
    $stages = array_filter($from[2]);
    preg_match_all('/--from=(\S+)/', $dockerfile, $copyFrom);
    $images = array_filter(
        array_merge($from[1], $copyFrom[1]),
        fn (string $image): bool => ! in_array($image, $stages, true),
    );

    expect($images)->not->toBeEmpty();
    foreach ($images as $image) {
        expect($image)->not->toContain(':latest')
            ->and($image)->toMatch('/:\d+\.\d+/', "{$image} is not pinned to at least major.minor");
    }

    preg_match('/node:(\d+)\./', $dockerfile, $imageNode);
    preg_match("/node-version:\s*'?(\d+)/", (string) file_get_contents(base_path('.github/workflows/tests.yml')), $ciNode);
    expect($imageNode[1] ?? null)->not->toBeNull()
        ->and($imageNode[1] ?? null)->toBe($ciNode[1] ?? null);
});

it('keeps secrets and local artefacts out of the image build context (CA2, TM13)', function () {
    $path = base_path('.dockerignore');
    expect(is_file($path))->toBeTrue('.dockerignore does not exist');

    $patterns = array_map('trim', file($path, FILE_IGNORE_NEW_LINES));
    foreach (['.env', '.git', 'public/hot'] as $pattern) {
        expect($patterns)->toContain($pattern);
    }
});
