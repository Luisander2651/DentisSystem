<?php

declare(strict_types=1);

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LogoutUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Auth\Domain\Service\LogoutService;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Sanctum's PersonalAccessToken::findToken() queries the database, so this file
// boots the framework even though it exercises the services directly.
uses(TestCase::class, RefreshDatabase::class);

/**
 * BR-22: the dead fallback path was removed from LogoutController because
 * auth:sanctum makes it unreachable over HTTP. These guards still exist in the
 * application and domain layers, so they are pinned here with direct unit tests
 * instead of HTTP tests.
 */
it('rejects a null actor with unauthenticatedContext', function () {
    $useCase = new LogoutUseCase(new LogoutService);

    expect(fn () => $useCase->execute(null))
        ->toThrow(AuthAplicationExceptions::class, 'No authenticated user context found.');
});

it('fails with tokenRevocationFailed when no token can be resolved', function () {
    $service = new LogoutService;
    $actor = new PatientModel;

    expect(fn () => $service->logout($actor, null))
        ->toThrow(AuthException::class, 'Unable to revoke the current token.');
});

it('fails with tokenRevocationFailed when the supplied token does not exist', function () {
    $service = new LogoutService;
    $actor = new PatientModel;

    expect(fn () => $service->logout($actor, 'not-a-real-token'))
        ->toThrow(AuthException::class, 'Unable to revoke the current token.');
});
