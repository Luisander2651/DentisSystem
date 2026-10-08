<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA18 and CA20 (TM1, TM2): someone signed in who opens a screen their role may
 * not see gets the "Sin permiso" page - in Spanish, with the way back to their start and
 * nothing of the screen - and a fixed Spanish body when JSON is asked for. Who may see what
 * does not change: every protected web route is denied here to every actor without permission.
 */

const DENIED_PATIENT_ID = '11111111-2222-4333-8444-555555555555';

const DENIED_ADMIN_SCREENS = ['/usuarios', '/contenido', '/tratamientos', '/pacientes', '/agenda'];

const DENIED_STAFF_SCREENS = ['/expedientes-clinicos', '/expedientes-clinicos/'.DENIED_PATIENT_ID];

const DENIED_INTERNAL_MESSAGES = [
    'Only administrators',
    'Only users',
    'Only staff',
    'Your account is inactive',
    'not allowed to access',
    'Forbidden',
    'This action is unauthorized',
];

/**
 * Every protected screen × every actor that may not open it.
 *
 * @return array<string, array{string, string}>
 */
function deniedScreensByActor(): array
{
    $cases = [];

    foreach (['asistente', 'doctor'] as $actor) {
        foreach (DENIED_ADMIN_SCREENS as $screen) {
            $cases["$actor en $screen"] = [$actor, $screen];
        }
    }

    foreach (['paciente', 'staff inactivo'] as $actor) {
        foreach ([...DENIED_ADMIN_SCREENS, ...DENIED_STAFF_SCREENS] as $screen) {
            $cases["$actor en $screen"] = [$actor, $screen];
        }
    }

    return $cases;
}

function actAsDeniedActor(CoreIntegrationTestCase $test, string $actor): UserModel|PatientModel
{
    return match ($actor) {
        'asistente' => (fn () => $this->actingAsNonAdminUser('Asistente'))->call($test),
        'doctor' => (fn () => $this->actingAsNonAdminUser('Doctor'))->call($test),
        'paciente' => (fn () => $this->actingAsPatient())->call($test),
        'staff inactivo' => (fn () => $this->actingAsInactiveAdmin())->call($test),
    };
}

function expectNoAccessDeniedDetails(TestResponse $response, string $screen): void
{
    foreach (DENIED_INTERNAL_MESSAGES as $message) {
        expect($response->getContent())->not->toContain($message);
    }

    expect($response->getContent())->not->toContain(ltrim($screen, '/'))
        ->and($response->getContent())->not->toContain('data-sidebar-root');
}

it('covers every protected web route of the application', function () {
    $protected = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => ! str_starts_with($route->uri(), 'api/')
            && collect($route->gatherMiddleware())->contains(fn (string $middleware): bool => $middleware === 'only.admin' || str_starts_with($middleware, 'staff')))
        ->map(fn (RoutingRoute $route): string => '/'.str_replace('{patientId}', DENIED_PATIENT_ID, $route->uri()))
        ->sort()
        ->values()
        ->all();

    $covered = [...DENIED_ADMIN_SCREENS, ...DENIED_STAFF_SCREENS];
    sort($covered);

    expect($protected)->toBe($covered);
});

it('shows the "Sin permiso" page to an actor without permission (abuse)', function (string $actor, string $screen) {
    config(['app.debug' => false]);
    actAsDeniedActor($this, $actor);

    $response = $this->get($screen);

    $response->assertForbidden();
    $response->assertViewIs('errors::403');
    $response->assertSee('No tienes permiso para ver esta pantalla');
    $response->assertSee('href="'.route('dashboard').'"', false);
    $response->assertSee('lang="es"', false);
    expectNoAccessDeniedDetails($response, $screen);
})->with(deniedScreensByActor())->skip('016: pendiente de T070');

it('answers a fixed Spanish body when an actor without permission asks for JSON (abuse)', function (string $actor, string $screen) {
    config(['app.debug' => false]);
    actAsDeniedActor($this, $actor);

    $response = $this->getJson($screen);

    $response->assertForbidden();
    $response->assertExactJson(['message' => 'No tienes permiso para ver esta pantalla.']);
})->with(deniedScreensByActor())->skip('016: pendiente de T071');

it('still lets each role into the screens it may see', function (string $role, array $screens) {
    $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    foreach ($screens as $screen) {
        $this->get($screen)->assertOk();
    }
})->with([
    'administrador' => ['Administrador', [...DENIED_ADMIN_SCREENS, '/expedientes-clinicos']],
    'asistente' => ['Asistente', ['/expedientes-clinicos', '/dashboard']],
    'doctor' => ['Doctor', ['/expedientes-clinicos', '/dashboard']],
]);
