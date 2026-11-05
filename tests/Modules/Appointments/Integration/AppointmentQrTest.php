<?php

declare(strict_types=1);

use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\DecodesQrSvg;

uses(AppointmentsIntegrationTestCase::class, DecodesQrSvg::class);

/*
 * Spec 024: GET /api/v1/appointments/{id}/qr answers the QR code of an appointment as SVG,
 * holding only its id, to the staff that can see its detail (plan → "Contratos y datos").
 */

function appointmentQrUrl(string $id): string
{
    return "/api/v1/appointments/{$id}/qr";
}

/**
 * The directives of the Content-Security-Policy of a response, by name.
 *
 * @return array<string, string>
 */
function appointmentQrCspDirectives(TestResponse $response): array
{
    $directives = [];

    foreach (explode(';', (string) $response->headers->get('Content-Security-Policy')) as $directive) {
        $parts = preg_split('/\s+/', trim($directive), 2);

        if ($parts[0] !== '') {
            $directives[$parts[0]] = $parts[1] ?? '';
        }
    }

    return $directives;
}

it('answers an SVG that holds exactly the appointment id (CA1)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->get(appointmentQrUrl($appointment->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('image/svg+xml')
        ->and($this->decodeQrSvg($response->getContent()))->toBe($appointment->id);
})->skip('024: pendiente de T022');

it('answers the same content every time it is asked (CA2)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $first = $this->get(appointmentQrUrl($appointment->id))->getContent();
    $second = $this->get(appointmentQrUrl($appointment->id))->getContent();

    expect($this->decodeQrSvg($second))->toBe($this->decodeQrSvg($first))
        ->and($this->decodeQrSvg($second))->toBe($appointment->id);
})->skip('024: pendiente de T022');

it('answers the QR code of a closed appointment too: who gets in is decided elsewhere', function (string $status) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => $status]);

    $response = $this->get(appointmentQrUrl($appointment->id));

    $response->assertOk();
    expect($this->decodeQrSvg($response->getContent()))->toBe($appointment->id);
})->with(['completada', 'cancelada', 'reprogramada'])->skip('024: pendiente de T022');

it('answers not found, like the detail, for an id without appointment (CA3)', function () {
    $this->actingAsAdmin();
    $id = (string) Str::uuid();

    $this->getJson(appointmentQrUrl($id))
        ->assertNotFound()
        ->assertExactJson(['error' => 'Appointment not found.']);
})->skip('024: pendiente de T022');

it('answers a bad request, like the internal query, for an id that is not a UUID (CA4)', function (string $id) {
    $this->actingAsAdmin();

    $this->getJson(appointmentQrUrl($id))
        ->assertBadRequest()
        ->assertExactJson(['error' => 'Invalid appointment id.']);
})->with([
    'text' => 'no-es-uuid',
    'number' => '123',
    'uuid without dashes' => '9b2e6c1a4f1d4c3e8a7b2d5f0e9c1a34',
])->skip('024: pendiente de T022');

it('refuses a patient the QR code of their own appointment (CA10, abuse)', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id]);

    $response = $this->getJson(appointmentQrUrl($appointment->id));

    $response->assertForbidden();
    expect($response->getContent())->not->toContain('<svg');
})->skip('024: pendiente de T022');

it('answers a generic 500 when drawing the QR code fails (CA11, abuse)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();
    $this->mock(QrCodeGeneratorInterface::class)
        ->shouldReceive('svg')->andThrow(new RuntimeException('detalle interno del generador'));

    $this->getJson(appointmentQrUrl($appointment->id))
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
})->skip('024: pendiente de T022');

it('answers a generic 500 when reading the appointment fails (CA11, abuse)', function () {
    $this->actingAsAdmin();
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RuntimeException('detalle interno de la base'));

    $this->getJson(appointmentQrUrl((string) Str::uuid()))
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
})->skip('024: pendiente de T022');

it('tells browsers and proxies not to store the QR code (CA12, TM4)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $cacheControl = (string) $this->get(appointmentQrUrl($appointment->id))->headers->get('Cache-Control');

    expect($cacheControl)->toContain('no-store')
        ->and($cacheControl)->toContain('private');
})->skip('024: pendiente de T022');

it('keeps the global policy that stops scripts inside the SVG when it is opened directly (TM6)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->get(appointmentQrUrl($appointment->id));
    $directives = appointmentQrCspDirectives($response);

    expect($directives)->toHaveKey('script-src')
        ->and($directives['script-src'])->not->toContain("'unsafe-inline'")
        ->and($directives['object-src'] ?? '')->toBe("'none'")
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
})->skip('024: pendiente de T022');

it('keeps the names of the patient and the doctor out of the QR code (TM5)', function () {
    $this->actingAsAdmin();
    $patient = $this->createPatient(['first_name' => 'Marisol', 'last_name' => 'Quintanar']);
    $doctor = $this->createUserWithRole('Doctor', ['first_name' => 'Evaristo', 'last_name' => 'Lizárraga']);
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id]);

    $svg = $this->get(appointmentQrUrl($appointment->id))->getContent();

    expect($svg)->not->toContain('Marisol')
        ->and($svg)->not->toContain('Quintanar')
        ->and($svg)->not->toContain('Evaristo')
        ->and($svg)->not->toContain('Lizárraga')
        ->and($this->decodeQrSvg($svg))->toBe($appointment->id);
})->skip('024: pendiente de T022');
