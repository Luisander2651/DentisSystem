<?php

declare(strict_types=1);

use App\Core\Middlewares\EnsureActivePatient;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA15, CA16, TM6): the portal lets through only an active patient. Staff of any role
 * and a patient whose account the clinic deactivated get 403, in JSON for the API and as the
 * "Sin permiso" page on a screen.
 */

function patientGateRequest(string $path, mixed $actor): Request
{
    $request = Request::create($path);
    $request->setUserResolver(fn () => $actor);

    return $request;
}

function passPatientGate(Request $request): mixed
{
    return (new EnsureActivePatient)->handle($request, fn (): Response => new Response('portal'));
}

it('lets an active patient through', function () {
    $response = passPatientGate(patientGateRequest('/api/v1/portal/appointments', new PatientModel(['status' => 'active'])));

    expect($response->getContent())->toBe('portal');
});

it('answers staff with 403 on the API', function () {
    $response = passPatientGate(patientGateRequest('/api/v1/portal/appointments', new UserModel(['status' => 'active'])));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe(['error' => 'Only patients can access this resource.']);
});

it('answers an inactive patient with 403 on the API', function () {
    $response = passPatientGate(patientGateRequest('/api/v1/portal/appointments', new PatientModel(['status' => 'inactive'])));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe(['error' => 'Your account is inactive.']);
});

it('answers staff and an inactive patient with the 403 page on a screen', function (mixed $actor) {
    passPatientGate(patientGateRequest('/mis-citas', $actor));
})->throws(HttpException::class)->with([
    'staff' => fn () => new UserModel(['status' => 'active']),
    'inactive patient' => fn () => new PatientModel(['status' => 'inactive']),
]);
