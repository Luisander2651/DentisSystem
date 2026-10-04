<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, /review R4, R5, R6, R9, R10, R18 and R30 (CA13, CA15): deploy.sh, rollback.sh
 * and restore.sh are run in a temporary clone. `docker`, compose.sh, backup.sh and
 * verify.sh are stand-ins that record their calls in order, so the tests assert what the
 * scripts do and in which order, not which words they contain.
 */

const STAND_INS = [
    'compose.sh' => <<<'SH'
        #!/usr/bin/env bash
        echo "compose $*" >> "$CALLS"
        case "$*" in
            *migrate*) [ -z "${FAIL_MIGRATE:-}" ] || exit 1 ;;
            up*) echo "state current=$(cat "$DENTISSA_DIR/.deploy/current" 2> /dev/null) previous=$(cat "$DENTISSA_DIR/.deploy/previous" 2> /dev/null)" >> "$CALLS" ;;
            *pg_dump*) printf 'PGDMP-stand-in' ;;
            *"exec -T db"*) cat > /dev/null ;;
        esac
        SH,
    'backup.sh' => <<<'SH'
        #!/usr/bin/env bash
        echo "backup $*" >> "$CALLS"
        echo "$BACKUP_DIR/stand-in.dump"
        SH,
    'verify.sh' => <<<'SH'
        #!/usr/bin/env bash
        echo "verify $*" >> "$CALLS"
        [ -z "${FAIL_VERIFY:-}" ] || exit 1
        SH,
    'restore.sh' => <<<'SH'
        #!/usr/bin/env bash
        echo "restore $*" >> "$CALLS"
        SH,
];

const DOCKER_STAND_IN = <<<'SH'
    #!/usr/bin/env bash
    echo "docker $*" >> "$CALLS"
    case "$1 $2" in
        "image ls") cat "$IMAGES" ;;
        "image rm") [ -z "${FAIL_IMAGE_RM:-}" ] || exit 1 ;;
        "compose exec") cat > /dev/null ;;
    esac
    SH;

function shellScript(string $heredoc): string
{
    return preg_replace('/^ {4,8}/m', '', $heredoc)."\n";
}

beforeEach(function () {
    $this->sandbox = storage_path('framework/testing/deploy-'.Str::uuid());
    $this->clone = $this->sandbox.'/clone';
    $this->calls = $this->sandbox.'/calls.log';
    File::makeDirectory($this->clone.'/docker/prod', 0700, true);
    File::makeDirectory($this->sandbox.'/bin', 0700, true);
    File::makeDirectory($this->sandbox.'/backups', 0700, true);
    File::makeDirectory($this->sandbox.'/manual/storage/app/public', 0700, true);
    file_put_contents($this->calls, '');
    file_put_contents($this->sandbox.'/images', '');
    file_put_contents($this->sandbox.'/bin/docker', shellScript(DOCKER_STAND_IN));
    chmod($this->sandbox.'/bin/docker', 0700);

    $this->env = [
        'PATH' => $this->sandbox.'/bin:'.getenv('PATH'),
        'HOME' => $this->sandbox,
        'USER' => 'tester',
        'CALLS' => $this->calls,
        'IMAGES' => $this->sandbox.'/images',
        'DENTISSA_DIR' => $this->clone,
        'MANUAL_DIR' => $this->sandbox.'/manual',
        'BACKUP_DIR' => $this->sandbox.'/backups',
        'DEPLOY_LOG' => $this->sandbox.'/deploys.log',
        'GIT_AUTHOR_NAME' => 'tester', 'GIT_AUTHOR_EMAIL' => 'tester@example.test',
        'GIT_COMMITTER_NAME' => 'tester', 'GIT_COMMITTER_EMAIL' => 'tester@example.test',
        'GIT_CONFIG_GLOBAL' => '/dev/null',
        // Removed from the environment: lib.sh prefers SUDO_USER, and inside a git hook these
        // would point the test's `git` at the real repository.
        'SUDO_USER' => false, 'GIT_DIR' => false, 'GIT_WORK_TREE' => false, 'GIT_INDEX_FILE' => false,
    ];

    /**
     * Builds the clone with the given real scripts (the rest are stand-ins) and tags v1 to v3.
     *
     * @param  list<string>  $real
     */
    $this->buildClone = function (array $real): void {
        foreach (['lib.sh', ...$real] as $script) {
            copy(base_path("docker/prod/{$script}"), "{$this->clone}/docker/prod/{$script}");
        }
        foreach (STAND_INS as $script => $body) {
            if (! in_array($script, $real, true)) {
                file_put_contents("{$this->clone}/docker/prod/{$script}", shellScript($body));
            }
        }
        foreach (glob("{$this->clone}/docker/prod/*.sh") as $script) {
            chmod($script, 0700);
        }
        file_put_contents("{$this->clone}/.gitignore", "/.deploy\n");

        foreach ([['init', '-q'], ['add', '-A'], ['commit', '-q', '-m', 'release'], ['tag', 'v1'], ['tag', 'v2'], ['tag', 'v3']] as $arguments) {
            (new Process(['git', ...$arguments], $this->clone, $this->env))->mustRun();
        }
    };

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     * @return array{0: int, 1: string}
     */
    $this->run = function (array $command, array $env = [], ?string $cwd = null, ?string $input = null): array {
        $process = new Process($command, $cwd ?? $this->clone, $env + $this->env, $input);
        $process->setTimeout(120);
        $process->run();

        return [(int) $process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
    };

    $this->state = function (?string $current, ?string $previous): void {
        File::ensureDirectoryExists("{$this->clone}/.deploy");
        foreach (['current' => $current, 'previous' => $previous] as $name => $tag) {
            if ($tag !== null) {
                file_put_contents("{$this->clone}/.deploy/{$name}", $tag."\n");
            }
        }
    };

    $this->recorded = fn (): array => file($this->calls, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $this->position = function (string $needle): ?int {
        foreach (($this->recorded)() as $index => $call) {
            if (str_contains($call, $needle)) {
                return $index;
            }
        }

        return null;
    };
    $this->deployState = fn (string $name): ?string => is_file("{$this->clone}/.deploy/{$name}")
        ? trim((string) file_get_contents("{$this->clone}/.deploy/{$name}"))
        : null;
    $this->lastLogLine = fn (): string => (string) last(file($this->sandbox.'/deploys.log', FILE_IGNORE_NEW_LINES));
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

it('migrates with the new image before switching versions (R6)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v1', null);

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2']);

    // Without the image's entrypoint: `artisan optimize` would recompile the views into the
    // storage volume the running version is still serving from (R36).
    $migrate = ($this->position)('compose run --rm --entrypoint php app artisan migrate --force');
    $up = ($this->position)('compose up -d');

    expect($exit)->toBe(0, $output)
        ->and($migrate)->not->toBeNull('the migration did not run in a one-off container of the new image')
        ->and($up)->not->toBeNull()
        ->and($migrate)->toBeLessThan($up)
        ->and(($this->position)('backup pre-v2'))->toBeLessThan($migrate);
});

it('leaves the running version and the state untouched when the migration fails (R4)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v1', null);

    [$exit] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2'], ['FAIL_MIGRATE' => '1']);

    expect($exit)->not->toBe(0)
        ->and(($this->position)('compose up -d'))->toBeNull('the new version was started although the migration failed')
        ->and(trim((string) file_get_contents("{$this->clone}/.deploy/current")))->toBe('v1')
        ->and(is_file("{$this->clone}/.deploy/previous"))->toBeFalse()
        ->and(($this->lastLogLine)())->toContain('action=deploy version=v2 result=failed');
});

it('writes the deploy state before starting the new version (R4)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v1', null);

    ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2']);

    expect(($this->recorded)())->toContain('state current=v2 previous=v1');
});

it('keeps the images of the previous version when the same tag is deployed again (R5)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v2', 'v1');
    file_put_contents($this->sandbox.'/images', "v0\nv1\nv2\n");

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2']);

    expect($exit)->toBe(0, $output)
        ->and(($this->position)('docker image rm dentissa-app:v1'))->toBeNull('the previous version lost its image')
        ->and(($this->position)('docker image rm dentissa-web:v1'))->toBeNull()
        ->and(($this->position)('docker image rm dentissa-app:v0'))->not->toBeNull('older images are still removed')
        ->and(trim((string) file_get_contents("{$this->clone}/.deploy/previous")))->toBe('v1');
});

it('does not record a verified deploy as failed when an old image cannot be removed (R30)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v1', null);
    file_put_contents($this->sandbox.'/images', "v0\nv1\nv2\n");

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2'], ['FAIL_IMAGE_RM' => '1']);

    expect($exit)->toBe(0, $output)
        ->and(($this->lastLogLine)())->toContain('action=deploy version=v2 result=ok');
});

it('records the operation itself as the last line of deploys.log (R9)', function () {
    ($this->buildClone)(['deploy.sh', 'rollback.sh']);
    ($this->state)('v1', null);

    ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2']);
    expect(($this->lastLogLine)())->toMatch('/ user=tester action=deploy version=v2 result=ok$/')
        ->and(($this->position)('verify --local --in-operation'))->not->toBeNull();

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/rollback.sh', 'v1']);
    expect($exit)->toBe(0, $output)
        ->and(($this->lastLogLine)())->toMatch('/ user=tester action=rollback version=v1 result=ok$/');
});

it('fails when the operation is not the last line of deploys.log (R9)', function () {
    ($this->buildClone)([]);
    $assert = 'source docker/prod/lib.sh; log_operation deploy v2 ok; assert_logged "$@"';

    expect(($this->run)(['bash', '-c', $assert, 'bash', 'deploy', 'v2'])[0])->toBe(0)
        ->and(($this->run)(['bash', '-c', $assert, 'bash', 'deploy', 'v3'])[0])->not->toBe(0)
        ->and(($this->run)(['bash', '-c', $assert, 'bash', 'rollback', 'v2'])[0])->not->toBe(0);
});

it('resolves a relative dump path against the directory of whoever runs rollback.sh (R18)', function () {
    ($this->buildClone)(['rollback.sh']);
    ($this->state)('v2', 'v1');
    $elsewhere = $this->sandbox.'/elsewhere';
    File::makeDirectory($elsewhere.'/dumps', 0700, true);
    file_put_contents($elsewhere.'/dumps/pre-v2.dump', 'PGDMP');

    [$exit, $output] = ($this->run)(
        ['bash', "{$this->clone}/docker/prod/rollback.sh", 'v1', '--restore', 'dumps/pre-v2.dump'],
        cwd: $elsewhere,
        input: "rollback\n",
    );

    expect($exit)->toBe(0, $output)
        ->and(($this->recorded)())->toContain("restore --yes {$elsewhere}/dumps/pre-v2.dump");
});

it('backs up the current database before restoring over it (R18)', function () {
    ($this->buildClone)(['restore.sh']);
    file_put_contents($this->sandbox.'/backups/pre-v2.dump', 'PGDMP');

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/restore.sh', '--yes', $this->sandbox.'/backups/pre-v2.dump']);

    $backup = ($this->position)('backup pre-restore');
    $restore = ($this->position)('compose exec -T db');

    expect($exit)->toBe(0, $output)
        ->and($backup)->not->toBeNull('no backup was taken before the restore')
        ->and($restore)->not->toBeNull()
        ->and($backup)->toBeLessThan($restore)
        ->and(($this->lastLogLine)())->toContain('action=restore version=pre-v2.dump result=ok');
});

it('does not back up through the production stack when restoring into the manual stack', function () {
    ($this->buildClone)(['restore.sh']);
    file_put_contents($this->sandbox.'/backups/pre-v2.dump', 'PGDMP');

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/restore.sh', '--yes', '--into-manual', $this->sandbox.'/backups/pre-v2.dump']);

    expect($exit)->toBe(0, $output)
        ->and(($this->position)('backup pre-restore'))->toBeNull()
        ->and(($this->position)('docker compose exec -T db'))->not->toBeNull();
});

it('refuses a dump whose name would break the operations log (R18)', function () {
    ($this->buildClone)(['restore.sh']);
    $dump = $this->sandbox.'/backups/pre v2 result=ok.dump';
    file_put_contents($dump, 'PGDMP');

    [$exit] = ($this->run)(['bash', 'docker/prod/restore.sh', '--yes', $dump]);

    expect($exit)->not->toBe(0)
        ->and(($this->position)('compose exec -T db'))->toBeNull('the restore ran with an invalid dump name')
        ->and(is_file($this->sandbox.'/deploys.log'))->toBeFalse('the invalid name reached the operations log');
});

it('only promotes a verified version to the one a rollback goes back to (R38)', function () {
    ($this->buildClone)(['deploy.sh']);
    ($this->state)('v1', null);
    file_put_contents($this->sandbox.'/images', 'v1
v2
v3
');

    // v2 starts but does not pass the checks, and nobody rolls back before the next deploy.
    [$failed] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2'], ['FAIL_VERIFY' => '1']);

    expect($failed)->not->toBe(0)
        ->and(($this->deployState)('current'))->toBe('v2')
        ->and(($this->deployState)('previous'))->toBe('v1')
        ->and(($this->deployState)('verified'))->toBe('v1');

    (new Process(['git', 'checkout', '-q', 'v3'], $this->clone, $this->env))->mustRun();
    file_put_contents($this->calls, '');
    [$exit, $output] = ($this->run)(['bash', 'docker/prod/deploy.sh', 'v3']);

    expect($exit)->toBe(0, $output)
        ->and(($this->deployState)('current'))->toBe('v3')
        ->and(($this->deployState)('previous'))->toBe('v1', 'a version that never passed the checks became the rollback target')
        ->and(($this->deployState)('verified'))->toBe('v3')
        ->and(($this->position)('docker image rm dentissa-app:v1'))->toBeNull('the last good version lost its image')
        ->and(($this->position)('docker image rm dentissa-app:v2'))->not->toBeNull();
});

it('records the version as verified after a deploy and after a rollback (R38)', function () {
    ($this->buildClone)(['deploy.sh', 'rollback.sh']);
    ($this->state)('v1', null);

    ($this->run)(['bash', 'docker/prod/deploy.sh', 'v2']);
    expect(($this->deployState)('verified'))->toBe('v2');

    ($this->run)(['bash', 'docker/prod/rollback.sh', 'v1']);
    expect(($this->deployState)('verified'))->toBe('v1')
        ->and(($this->deployState)('current'))->toBe('v1')
        ->and(($this->deployState)('previous'))->toBe('v2');
});

it('deletes the daily dumps of seven full days and keeps the rest (R37)', function () {
    ($this->buildClone)(['backup.sh']);
    $aged = function (string $name, float $days): string {
        $path = "{$this->sandbox}/backups/{$name}";
        file_put_contents($path, 'PGDMP');
        touch($path, time() - (int) round($days * 86400));

        return $path;
    };
    $overdue = $aged('daily-20260101T030000Z.dump', 7.05);
    $recent = $aged('daily-20260102T030000Z.dump', 6.5);
    $beforeDeploy = $aged('pre-v1-20251201T000000Z.dump', 30);

    [$exit, $output] = ($this->run)(['bash', 'docker/prod/backup.sh', 'daily']);

    expect($exit)->toBe(0, $output)
        ->and(is_file($overdue))->toBeFalse('a daily dump of seven full days was kept')
        ->and(is_file($recent))->toBeTrue()
        ->and(is_file($beforeDeploy))->toBeTrue()
        ->and(glob("{$this->sandbox}/backups/daily-*.dump"))->toHaveCount(2)
        ->and(($this->lastLogLine)())->toContain('action=backup version=daily result=ok');
});
