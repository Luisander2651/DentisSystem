<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, T051: the production scripts run with `set -o pipefail`. A reader that stops
 * early (`grep -q`, `head`) makes the writer die with SIGPIPE, so the pipeline fails even
 * when the text was found — verify.sh reported "APP_DEBUG is off" as failed on the droplet
 * with debug_mode false. Output is captured first and matched afterwards.
 */

it('never pipes into a reader that stops early in the production scripts', function (string $script) {
    $lines = file(base_path("docker/prod/{$script}"), FILE_IGNORE_NEW_LINES);

    foreach ($lines as $number => $line) {
        if (str_starts_with(ltrim($line), '#')) {
            continue;
        }

        expect(preg_match('/\|\s*(grep\s+-[a-zA-Z]*q|head\b)/', $line))
            ->toBe(0, "{$script}:".($number + 1).' pipes into a reader that stops early: '.trim($line));
    }
})->with(['backup.sh', 'compose.sh', 'deploy.sh', 'lib.sh', 'restore.sh', 'rollback.sh', 'verify.sh']);

it('gives php-fpm time to start before failing the /up check (T054)', function () {
    // rollback.sh waits for `artisan --version`, which answers while the entrypoint still
    // runs `artisan optimize` and before php-fpm listens, so nginx answers 502 for a moment.
    $verify = (string) file_get_contents(base_path('docker/prod/verify.sh'));
    preg_match('/^health_answers\(\) \{\n(.*?)\n\}/ms', $verify, $body);

    expect($body[1] ?? '')->toMatch('/for _ in \$\(seq 1 \d+\)/')
        ->and($body[1] ?? '')->toContain('sleep');
});

it('restores a dump exactly, atomically and without wiping the database on a bad dump (T054)', function () {
    // pg_restore --clean only drops what the dump contains, so tables created after the
    // backup (a newer version's migrations) survived the T054 rollback and would break the
    // next migrate. The schema is reset and the dump applied in one transaction, and only
    // after pg_restore has read the whole dump.
    $restore = (string) file_get_contents(base_path('docker/prod/restore.sh'));

    expect($restore)->not->toContain('--clean')
        ->and($restore)->toContain('DROP SCHEMA public CASCADE')
        ->and($restore)->toContain('--single-transaction')
        ->and($restore)->toContain('ON_ERROR_STOP=1')
        ->and($restore)->toMatch('/pg_restore --no-owner -f "\$sql"/');
});
