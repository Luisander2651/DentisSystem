<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
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
    // The check is run with a curl that fails at first: it must keep trying, and give up.
    $health = function (string $curl): array {
        $process = new Process(
            ['bash', '-c', 'source docker/prod/verify.sh; calls=0; sleep() { :; }; '.$curl.' health_answers; status=$?; echo "calls=$calls"; exit $status'],
            base_path(),
        );
        $process->run();

        return [(int) $process->getExitCode(), trim($process->getOutput())];
    };

    expect($health('curl() { calls=$((calls + 1)); [ "$calls" -ge 3 ]; };'))->toBe([0, 'calls=3'])
        ->and($health('curl() { calls=$((calls + 1)); return 7; };'))->toBe([1, 'calls=15']);
});

it('restores a dump exactly, atomically and without wiping the database on a bad dump (T054)', function () {
    // pg_restore's clean option only drops what the dump contains, so tables created after
    // the backup (a newer version's migrations) survived the T054 rollback and would break
    // the next migrate. The command needs a PostgreSQL server, so here it is read (without
    // comments) and its order checked; DeployScriptsBehaviourTest runs the script around it.
    $restore = (string) preg_replace('/^\s*#.*$/m', '', (string) file_get_contents(base_path('docker/prod/restore.sh')));

    $convert = strpos($restore, 'pg_restore --no-owner -f "$sql"');
    $reset = strpos($restore, 'DROP SCHEMA public CASCADE');
    $apply = strpos($restore, 'psql -X -q -v ON_ERROR_STOP=1 --single-transaction');

    expect($restore)->not->toContain('--clean')
        ->and($convert)->not->toBeFalse('the dump is not converted to SQL first')
        ->and($reset)->not->toBeFalse('the schema is not reset')
        ->and($apply)->not->toBeFalse('the SQL is not applied in one transaction that stops on error')
        ->and($convert)->toBeLessThan($reset)
        ->and($reset)->toBeLessThan($apply);
});
