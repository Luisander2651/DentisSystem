<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, /review R3, R9, R10, R11, R12 and R15: the checks of docker/prod/verify.sh are
 * run, not read. Each case loads the script with `source` and calls one check with
 * temporary paths and stand-ins for `df`, `curl` or the headers, so a check that cannot
 * fail (or that fails for the wrong reason) shows up in CI and not on the droplet.
 */

beforeEach(function () {
    $this->sandbox = storage_path('framework/testing/verify-'.Str::uuid());
    $this->backups = $this->sandbox.'/backups';
    File::makeDirectory($this->backups, 0700, true);
    file_put_contents($this->sandbox.'/deploys.log', '');
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

/**
 * Runs bash with docker/prod/verify.sh loaded and returns [exit code, output].
 *
 * @param  array<string, string>  $env
 * @return array{0: int, 1: string}
 */
function verifyCheck(object $test, string $body, array $env = []): array
{
    $process = new Process(['bash', '-c', 'source "$VERIFY_SCRIPT"; '.$body], base_path(), $env + [
        'VERIFY_SCRIPT' => base_path('docker/prod/verify.sh'),
        'BACKUP_DIR' => $test->backups,
        'DEPLOY_LOG' => $test->sandbox.'/deploys.log',
        'DENTISSA_DIR' => $test->sandbox,
    ]);
    $process->run();

    return [(int) $process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
}

/**
 * Runs verify.sh itself, with a PATH that holds only the given tools and stand-ins.
 *
 * @param  list<string>  $arguments
 * @param  list<string>  $tools  real tools linked into the PATH
 * @param  array<string, string>  $standIns  name => shell body
 * @return array{0: int, 1: string}
 */
function runVerify(object $test, array $arguments, array $tools, array $standIns = []): array
{
    $bin = $test->sandbox.'/bin';
    File::makeDirectory($bin, 0700, true);
    foreach ($tools as $tool) {
        $real = trim((string) shell_exec('command -v '.escapeshellarg($tool)));
        expect($real)->not->toBe('', "{$tool} is not installed in the test environment");
        symlink($real, "{$bin}/{$tool}");
    }
    foreach ($standIns as $name => $body) {
        file_put_contents("{$bin}/{$name}", "#!/bin/sh\n{$body}\n");
        chmod("{$bin}/{$name}", 0700);
    }

    $process = new Process(
        ["{$bin}/bash", base_path('docker/prod/verify.sh'), ...$arguments],
        base_path(),
        ['PATH' => $bin, 'BACKUP_DIR' => $test->backups, 'DEPLOY_LOG' => $test->sandbox.'/deploys.log'],
    );
    $process->setTimeout(60);
    $process->run();

    return [(int) $process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
}

const VERIFY_TOOLS = ['bash', 'dirname', 'cat', 'grep', 'sed', 'awk', 'tail', 'seq', 'find', 'stat', 'df', 'sleep'];

function dumpAgedHours(object $test, string $name, float $hours): void
{
    $path = "{$test->backups}/{$name}";
    file_put_contents($path, 'PGDMP');
    chmod($path, 0600);
    touch($path, time() - (int) round($hours * 3600));
}

it('can be loaded without running any check', function () {
    [$exit, $output] = verifyCheck($this, 'echo loaded');

    expect($output)->toBe("loaded\n")
        ->and($exit)->toBe(0);
});

it('passes the disk check only below 80 percent (R3)', function (int $used, int $expectedExit) {
    $df = 'df() { printf "Filesystem 1K-blocks Used Available Use%% Mounted on\n/dev/x 100 1 1 %s%% /\n" "$USED"; }; ';

    [$exit] = verifyCheck($this, $df.'disk_has_room', ['USED' => (string) $used]);

    expect($exit)->toBe($expectedExit);
})->with([
    '9% has room' => [9, 0],
    '79% has room' => [79, 0],
    '80% is full' => [80, 1],
    '100% is full' => [100, 1],
]);

it('asks for a daily backup of the last 25 hours outside an operation (R9)', function () {
    dumpAgedHours($this, 'pre-v1.0.0-20260101T000000Z.dump', 0.1);

    // A deploy has just written its pre-<tag> dump: that is enough while it runs…
    expect(verifyCheck($this, 'IN_OPERATION=true; recent_backup_exists')[0])->toBe(0)
        // …but on its own, verify.sh must notice that the daily cron is not running.
        ->and(verifyCheck($this, 'IN_OPERATION=false; recent_backup_exists')[0])->toBe(1);

    dumpAgedHours($this, 'daily-20260101T030000Z.dump', 26);
    expect(verifyCheck($this, 'IN_OPERATION=false; recent_backup_exists')[0])->toBe(1);

    dumpAgedHours($this, 'daily-20260102T030000Z.dump', 2);
    expect(verifyCheck($this, 'IN_OPERATION=false; recent_backup_exists')[0])->toBe(0);
});

it('treats a daily backup of seven full days as overdue for rotation (R15)', function () {
    dumpAgedHours($this, 'daily-20260101T030000Z.dump', 6 * 24 + 12);
    expect(verifyCheck($this, 'old_daily_backups_are_rotated')[0])->toBe(0);

    dumpAgedHours($this, 'daily-20251225T030000Z.dump', 7 * 24 + 1);
    expect(verifyCheck($this, 'old_daily_backups_are_rotated')[0])->toBe(1);
});

it('does not take a failed request for a missing header (R9)', function () {
    $present = 'headers_of() { printf "HTTP/2 200\r\nserver: nginx/1.30.5\r\nx-frame-options: DENY\r\n"; }; ';
    $absent = 'headers_of() { printf "HTTP/2 200\r\nserver: nginx\r\n"; }; ';
    $failed = 'headers_of() { return 7; }; ';

    expect(verifyCheck($this, $present.'lacks_header "server: nginx/" https://example.test/')[0])->toBe(1)
        ->and(verifyCheck($this, $absent.'lacks_header "server: nginx/" https://example.test/')[0])->toBe(0)
        ->and(verifyCheck($this, $failed.'lacks_header "server: nginx/" https://example.test/')[0])->toBe(1);
});

it('rejects an --origin that is not an IP address before checking anything (R12)', function (string $origin) {
    [$exit, $output] = runVerify($this, ['--remote', 'example.test', '--origin', $origin], [...VERIFY_TOOLS, 'timeout'], [
        'curl' => 'exit 7',
    ]);

    expect($exit)->not->toBe(0)
        ->and($output)->toContain('--origin')
        ->and($output)->not->toContain('remote checks');
})->with([
    'command injection' => ['203.0.113.1;touch /tmp/pwned'],
    'host name' => ['example.test'],
    'empty octet' => ['203.0..1'],
]);

it('accepts IPv4 and IPv6 origins', function (string $origin) {
    // `timeout` is a stand-in too: the real one would open connections to the address.
    [, $output] = runVerify($this, ['--remote', 'example.test', '--origin', $origin], VERIFY_TOOLS, [
        'curl' => 'exit 7',
        'timeout' => 'exit 124',
    ]);

    expect($output)->toContain('remote checks');
})->with(['203.0.113.10', '2001:db8::10']);

it('refuses to report closed ports when timeout is not installed (R11)', function () {
    [$exit, $output] = runVerify($this, ['--remote', 'example.test', '--origin', '203.0.113.10'], VERIFY_TOOLS, [
        'curl' => 'exit 7',
    ]);

    expect($exit)->not->toBe(0)
        ->and($output)->toContain('timeout')
        ->and($output)->not->toContain('is closed on the droplet IP');
});
