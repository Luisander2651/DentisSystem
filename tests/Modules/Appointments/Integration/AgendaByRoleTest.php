<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

/*
 * Spec 018: the agenda by role. The assistant manages the whole agenda (CA1-CA3); the doctor
 * only ever receives the appointments assigned to them, whatever the request asks for (CA5,
 * CA8); an empty list is a 200, not an error (D10). The clock is fixed the day before the
 * dates this file books, so they stay in the future.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-08-31 10:00:00', 'America/Mexico_City'));
    $this->fakeTwilio();
});

/**
 * @return array{0: UserModel, 1: UserModel}
 */
function twoDoctorsWithAppointments(object $test): array
{
    return (function (): array {
        $doctor = $this->createUserWithRole('doctor');
        $otherDoctor = $this->createUserWithRole('doctor');

        $this->createAppointment(['user_id' => $doctor->id, 'date' => '2026-08-31', 'time' => '12:00']);
        $this->createAppointment(['user_id' => $doctor->id, 'date' => '2026-09-01', 'time' => '09:00', 'status' => 'completada']);
        $this->createAppointment(['user_id' => $otherDoctor->id, 'date' => '2026-08-31', 'time' => '13:00']);
        $this->createAppointment(['user_id' => $otherDoctor->id, 'date' => '2026-09-01', 'time' => '11:00']);

        return [$doctor, $otherDoctor];
    })->call($test);
}

function userIdsOf(array $appointments): array
{
    return collect($appointments)->pluck('user_id')->unique()->values()->all();
}

it('lists every appointment of the clinic for the assistant', function () {
    twoDoctorsWithAppointments($this);
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->getJson($this->appointmentsUrl());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(4);
})->skip('018: pendiente de T023');

it('lists every appointment of today for the assistant', function () {
    twoDoctorsWithAppointments($this);
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
})->skip('018: pendiente de T023');

it('lets the assistant book an appointment with the same rules as the administrator', function () {
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertCreated();
    $this->assertDatabaseCount('appointments', 1);
})->skip('018: pendiente de T021');

it('rejects the assistant booking an overlapping slot or a moment that already passed', function () {
    $this->actingAsNonAdminUser('Asistente');
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '10:00']);

    $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload([
        'treatment_id' => (string) $treatment->id,
        'time' => '10:15',
    ]))->assertStatus(409);

    $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['date' => '2026-08-30']))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'No puedes agendar una cita en una fecha u hora que ya pasó.');

    $this->assertDatabaseCount('appointments', 1);
})->skip('018: pendiente de T021');

it('lets the assistant reschedule and cancel an appointment', function () {
    $this->actingAsNonAdminUser('Asistente');
    $toMove = $this->createAppointment(['date' => '2026-09-01', 'time' => '10:00']);
    $toCancel = $this->createAppointment(['date' => '2026-09-02', 'time' => '10:00']);

    $this->putJson($this->appointmentUrl($toMove->id), ['date' => '2026-09-03', 'time' => '11:00'])->assertOk();
    $this->putJson($this->appointmentUrl($toCancel->id), ['status' => 'cancelada'])->assertOk();

    $this->assertDatabaseHas('appointments', ['id' => $toMove->id, 'date' => '2026-09-03', 'status' => 'reprogramada']);
    $this->assertDatabaseHas('appointments', ['id' => $toCancel->id, 'status' => 'cancelada']);
})->skip('018: pendiente de T021');

it('gives the doctor only their own appointments, whatever filter the request sends', function (array $query) {
    [$doctor, $otherDoctor] = twoDoctorsWithAppointments($this);
    $this->actingAs($doctor, 'sanctum');

    $query = array_map(fn ($value) => $value === ':other' ? $otherDoctor->id : $value, $query);
    $response = $this->getJson($this->appointmentsUrl().'?'.http_build_query($query));

    $response->assertOk();
    expect($response->json('data'))->not->toBeEmpty();
    expect(userIdsOf($response->json('data')))->toBe([$doctor->id]);
})->with([
    'sin filtros' => [[]],
    'user_id de otro doctor' => [['user_id' => ':other']],
    'doctor_id de otro doctor' => [['doctor_id' => ':other']],
    'un día con citas de los dos' => [['date' => '2026-08-31']],
    'un estado' => [['status' => 'asignada']],
])->skip('018: pendiente de T023');

it('gives the doctor only their own appointments of today', function () {
    [$doctor, $otherDoctor] = twoDoctorsWithAppointments($this);
    $this->actingAs($doctor, 'sanctum');

    $response = $this->getJson($this->agendaTodayAppointmentsUrl().'?user_id='.$otherDoctor->id);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect(userIdsOf($response->json('data')))->toBe([$doctor->id]);
})->skip('018: pendiente de T023');

it('answers an empty list, not an error, to a doctor without appointments', function () {
    twoDoctorsWithAppointments($this);
    $this->actingAsNonAdminUser('Doctor');

    $this->getJson($this->appointmentsUrl())
        ->assertOk()
        ->assertExactJson(['message' => 'Appointments retrieved successfully', 'data' => []]);

    $this->getJson($this->agendaTodayAppointmentsUrl())
        ->assertOk()
        ->assertExactJson(['data' => []]);
})->skip('018: pendiente de T023 y T039');

it('keeps every appointment for the administrator', function () {
    twoDoctorsWithAppointments($this);
    $this->actingAsAdmin();

    $this->getJson($this->appointmentsUrl())->assertOk()->assertJsonCount(4, 'data');
    $this->getJson($this->agendaTodayAppointmentsUrl())->assertOk()->assertJsonCount(2, 'data');
});

it('answers an empty list to the administrator when a filter has no appointments', function () {
    twoDoctorsWithAppointments($this);
    $this->actingAsAdmin();

    $this->getJson($this->appointmentsUrl().'?status=cancelada')
        ->assertOk()
        ->assertExactJson(['message' => 'Appointments retrieved successfully', 'data' => []]);
})->skip('018: pendiente de T039');
