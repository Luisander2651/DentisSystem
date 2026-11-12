<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Infrastructure\Http\Resources\PortalAppointmentResource;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA3, CA17, TM7): an appointment of the portal carries only what the patient may see.
 * No ids of the patient, the doctor or the treatment, and no WhatsApp reminder.
 */

it('gives exactly the six fields of a portal appointment', function () {
    $appointment = AppointmentEntity::fromPrimitives(
        id: '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34',
        date: '2026-11-12',
        time: '10:30:00',
        whatsappReminder: true,
        status: 'reprogramada',
        treatmentId: '1',
        userId: '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f',
        patientId: '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
        treatmentName: 'Limpieza dental',
        userFirstName: 'Daniela',
        userLastName: 'Prueba',
        patientName: 'Ana Prueba',
        treatmentTime: '30',
        createdAt: '2026-09-01 09:00:00',
        updatedAt: '2026-09-01 09:00:00',
    );

    $data = (new PortalAppointmentResource($appointment))->toArray(Request::create('/'));

    expect($data)->toBe([
        'id' => '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34',
        'date' => '2026-11-12',
        'time' => '10:30',
        'status' => 'reprogramada',
        'treatment_name' => 'Limpieza dental',
        'doctor_name' => 'Daniela Prueba',
    ]);
});
