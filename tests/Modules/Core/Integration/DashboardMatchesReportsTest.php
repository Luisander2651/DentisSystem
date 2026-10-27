<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 020 (CA10, A10): the figures of the month on the administrator's dashboard come from
 * reports/monthly with the month the view hands over (data-current-month), which is the month
 * in course. Like "Reportes", it counts the whole month, also an appointment booked for a day
 * still to come, and compares it with the month before.
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
});

it('hands over the month in course and its figures are the ones of reports/monthly for the whole month', function () {
    $admin = $this->actingAsAdmin();
    $treatment = TreatmentModel::query()->create(['name' => 'Limpieza dental de prueba', 'description' => 'Limpieza', 'time' => 30]);

    $october = array_map(fn (): string => $this->createPatient()->id, range(1, 3));
    $september = $this->createPatient();
    $september->forceFill(['created_at' => '2026-09-10 10:00:00'])->save();

    $book = fn (string $date, string $status, string $patientId) => AppointmentModel::query()->create([
        'id' => (string) Str::uuid(),
        'date' => $date,
        'time' => '10:00',
        'whatsapp_reminder' => false,
        'status' => $status,
        'treatment_id' => $treatment->id,
        'user_id' => $admin->id,
        'patient_id' => $patientId,
    ]);

    $book('2026-10-05', 'completada', $october[0]);
    $book('2026-10-14', 'cancelada', $october[1]);
    $book('2026-10-21', 'asignada', $october[2]);
    $book('2026-10-22', 'asignada', $october[0]);
    $book('2026-10-28', 'asignada', $september->id);
    $book('2026-09-08', 'completada', $september->id);
    $book('2026-09-24', 'completada', $september->id);

    $page = $this->get('/dashboard')->assertOk()->getContent();
    preg_match('/data-current-month="(\d{4}-\d{2})"/', $page, $match);

    expect($match[1] ?? null)->toBe('2026-10');

    $this->getJson('/api/v1/reports/monthly?month='.$match[1])
        ->assertOk()
        ->assertJsonPath('data.is_current', true)
        ->assertJsonPath('data.previous_short_label', 'septiembre')
        ->assertJsonPath('data.appointments.total.value', 5)
        ->assertJsonPath('data.appointments.total.previous', 2)
        ->assertJsonPath('data.appointments.total.difference', 3)
        ->assertJsonPath('data.patients.new.value', 3)
        ->assertJsonPath('data.patients.new.previous', 1)
        ->assertJsonPath('data.patients.new.difference', 2)
        ->assertJsonPath('data.patients.active', ['value' => 4, 'as_of' => '2026-10-22']);
});
