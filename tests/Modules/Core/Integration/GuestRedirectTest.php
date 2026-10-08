<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA21 (TM7): a visitor without a session who opens a protected screen is sent to
 * sign in, the same way for every one of them, so the new error pages tell nobody which
 * screens exist.
 */

const GUEST_PATIENT_ID = '11111111-2222-4333-8444-555555555555';

const GUEST_PROTECTED_SCREENS = [
    '/logout',
    '/dashboard',
    '/expedientes-clinicos',
    '/expedientes-clinicos/'.GUEST_PATIENT_ID,
    '/usuarios',
    '/contenido',
    '/tratamientos',
    '/pacientes',
    '/agenda',
];

it('covers every web route that asks for a session', function () {
    $protected = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => ! str_starts_with($route->uri(), 'api/') && in_array('auth:sanctum', $route->gatherMiddleware(), true))
        ->map(fn (RoutingRoute $route): string => '/'.str_replace('{patientId}', GUEST_PATIENT_ID, $route->uri()))
        ->sort()
        ->values()
        ->all();

    $covered = GUEST_PROTECTED_SCREENS;
    sort($covered);

    expect($protected)->toBe($covered);
});

it('sends a visitor without a session to sign in', function (string $screen) {
    $this->get($screen)->assertRedirect(route('login'));
})->with(GUEST_PROTECTED_SCREENS);
