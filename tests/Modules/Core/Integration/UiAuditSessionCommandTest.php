<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Process\Process;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, TM5: `ui:audit-session {rol}` hands `npm run test:ui` a session without any
 * password living in the repository. Neither it nor `ui:audit-data` exists when the
 * application boots in production, and it only issues sessions of seeded accounts.
 */

/**
 * Boots a real Artisan process in the given environment and returns it finished.
 *
 * @param  list<string>  $arguments
 */
function artisanIn(string $environment, array $arguments): Process
{
    $process = new Process([PHP_BINARY, 'artisan', ...$arguments], base_path(), ['APP_ENV' => $environment, 'LOG_CHANNEL' => 'null']);
    $process->run();

    return $process;
}

/**
 * @return list<string>
 */
function artisanCommandNamesIn(string $environment): array
{
    $list = json_decode(artisanIn($environment, ['list', '--format=json'])->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    return array_column($list['commands'], 'name');
}

it('does not register the ui-audit commands when the application boots in production (abuse)', function () {
    $commands = artisanCommandNamesIn('production');

    expect($commands)->toContain('inspire')
        ->and($commands)->not->toContain('ui:audit-session')
        ->and($commands)->not->toContain('ui:audit-data');
});

it('cannot run the ui-audit commands when the application boots in production (abuse)', function (array $arguments) {
    $process = artisanIn('production', $arguments);

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getOutput().$process->getErrorOutput())->toContain('no commands defined in the "ui" namespace');
})->with([
    'session' => [['ui:audit-session', 'administrador']],
    'data' => [['ui:audit-data', '--clean']],
]);

it('registers both commands when the application boots in testing', function () {
    expect(artisanCommandNamesIn('testing'))->toContain('ui:audit-session', 'ui:audit-data');
});

it('issues a session of the seeded account of each role', function (string $role, string $email) {
    Storage::fake('public');
    (new UiAuditSeeder)->run();

    expect(Artisan::call('ui:audit-session', ['rol' => $role]))->toBe(0);

    $token = PersonalAccessToken::findToken(trim(Artisan::output()));

    expect($token)->not->toBeNull()
        ->and($token->tokenable->email)->toBe($email);
})->with([
    'administrador' => ['administrador', UiAuditSeeder::STAFF_EMAILS['administrador']],
    'asistente' => ['asistente', UiAuditSeeder::STAFF_EMAILS['asistente']],
    'doctor' => ['doctor', UiAuditSeeder::STAFF_EMAILS['doctor']],
    'paciente' => ['paciente', UiAuditSeeder::PATIENT_EMAIL],
]);

it('issues no session when the seeded accounts do not exist, even if others do (abuse)', function () {
    $this->createUserWithRole('Administrador');
    $this->createPatient();

    expect(Artisan::call('ui:audit-session', ['rol' => 'administrador']))->not->toBe(0)
        ->and(Artisan::call('ui:audit-session', ['rol' => 'paciente']))->not->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0)
        ->and(UserModel::query()->count())->toBe(1)
        ->and(PatientModel::query()->count())->toBe(1);
});

it('rejects anything that is not one of its roles (abuse)', function (string $role) {
    Storage::fake('public');
    (new UiAuditSeeder)->run();
    $foreign = $this->createUserWithRole('Administrador', ['email' => 'root@example.com']);

    expect(Artisan::call('ui:audit-session', ['rol' => $role]))->not->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0)
        ->and($foreign->tokens()->count())->toBe(0);
})->with(['root@example.com', 'Administrador ', 'inactivo', 'admin', '']);

it('issues no session outside local and testing (abuse)', function () {
    Storage::fake('public');
    (new UiAuditSeeder)->run();
    $this->app['env'] = 'production';

    expect(Artisan::call('ui:audit-session', ['rol' => 'administrador']))->not->toBe(0)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});
