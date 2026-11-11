<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Tests\Modules\Auth\Integration\AuthIntegrationTestCase;

uses(AuthIntegrationTestCase::class);

/*
 * Spec 025 (RS1.c, CA14, CA16, TM4): each kind of account has its own guard, so the token of one
 * kind is never taken for the other. The requests carry real tokens, never actingAs(), which
 * would skip the provider check; the guards forget their user between requests (A11).
 */

it('reads a patient token only through the patient guard', function () {
    $token = $this->createPatient()->createToken('test')->plainTextToken;

    Request::instance()->headers->set('Authorization', "Bearer {$token}");

    expect(Auth::guard('sanctum')->user())->toBeNull()
        ->and(Auth::guard('patient')->user())->toBeInstanceOf(PatientModel::class);
});

it('reads a staff token only through the staff guard', function () {
    $token = $this->createUserWithRole('administrador')->createToken('test')->plainTextToken;

    Request::instance()->headers->set('Authorization', "Bearer {$token}");

    expect(Auth::guard('patient')->user())->toBeNull()
        ->and(Auth::guard('sanctum')->user())->toBeInstanceOf(UserModel::class);
});

it('answers a patient token on a staff route with 403, not 401', function () {
    $token = $this->createPatient()->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/appointments')
        ->assertForbidden();
});

it('still lets a staff token through a staff route', function () {
    $token = $this->createUserWithRole('administrador')->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/appointments')
        ->assertOk();
});

it('still lets a patient log out with their token', function () {
    $patient = $this->createPatient();
    $token = $patient->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($patient->tokens()->count())->toBe(0);
});

it('answers a patient token on a staff screen with "Sin permiso"', function () {
    $token = $this->createPatient()->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get('/agenda')
        ->assertForbidden();
});
