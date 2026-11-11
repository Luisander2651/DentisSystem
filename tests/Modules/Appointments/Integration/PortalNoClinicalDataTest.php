<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 025 (CA17, TM7, P11): whatever the patient reads in the portal, no answer carries the
 * reason, symptoms, diagnosis, procedure or observations of the follow-up, the contact data of
 * anyone, or anything of another patient. Every one of those values carries a unique mark, and
 * no mark may appear in any answer of the portal.
 */

it('never sends internal clinical data, contact data or data of other patients', function () {
    $this->travelTo(Carbon::parse('2026-09-17 11:40:00', 'America/Mexico_City'));
    $patient = $this->actingAsPatient();
    $doctor = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba', 'email' => 'marca-correo-doctora@example.com']);

    $completed = $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id, 'date' => '2026-09-10', 'status' => 'completada']);
    $open = $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id, 'date' => '2026-09-20', 'status' => 'asignada']);

    $tracking = AppointmentTrackingModel::create([
        'id' => (string) Str::uuid(),
        'appointment_id' => $completed->id,
        'reason' => 'marca-motivo',
        'symptoms' => ['marca-sintoma'],
        'diagnosis' => 'marca-diagnostico',
        'procedure_performed' => 'marca-procedimiento',
        'observations' => 'marca-observaciones',
        'recommendations' => 'Cepillarse tres veces al día',
    ]);
    AppointmentTrackingPrescriptionModel::create([
        'id' => (string) Str::uuid(),
        'appointment_tracking_id' => $tracking->id,
        'medication' => 'Amoxicilina',
        'dosage' => '500 mg',
        'duration_days' => 7,
        'daily_frequency' => 3,
        'instructions' => 'Tomar con alimentos',
    ]);

    $other = $this->createPatient(['first_name' => 'marca-nombre-otro', 'last_name' => 'Paciente', 'email' => 'marca-correo-otro@example.com']);
    $foreign = $this->createAppointment(['patient_id' => $other->id, 'date' => '2026-09-18', 'status' => 'asignada']);

    $answers = [
        $this->getJson('/api/v1/portal/appointments')->assertOk()->getContent(),
        $this->getJson('/api/v1/portal/appointments/next')->assertOk()->getContent(),
        $this->getJson('/api/v1/portal/appointments/'.$completed->id.'/prescriptions')->assertOk()->getContent(),
        $this->get('/api/v1/portal/appointments/'.$open->id.'/qr')->assertOk()->getContent(),
        $this->getJson('/api/v1/portal/appointments/'.$foreign->id.'/prescriptions')->assertNotFound()->getContent(),
        $this->getJson('/api/v1/portal/appointments/'.$foreign->id.'/qr')->assertNotFound()->getContent(),
    ];

    $everything = implode("\n", $answers);

    foreach (['marca-motivo', 'marca-sintoma', 'marca-diagnostico', 'marca-procedimiento', 'marca-observaciones', 'marca-correo-doctora', 'marca-nombre-otro', 'marca-correo-otro', $other->id, $foreign->id, $patient->email] as $mark) {
        expect($everything)->not->toContain($mark);
    }

    expect($everything)->toContain('Cepillarse tres veces')
        ->and($everything)->toContain('Amoxicilina');
});
