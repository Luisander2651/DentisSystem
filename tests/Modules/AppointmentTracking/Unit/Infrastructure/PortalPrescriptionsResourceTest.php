<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Infrastructure\Http\Resources\PortalPrescriptionsResource;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA5, CA17, TM7): what the portal says about the follow-up of an appointment is a
 * closed list: the recommendations and, for each prescription, its five fields. No ids, and
 * nothing of the reason, symptoms, diagnosis, procedure or observations.
 */

it('gives only the recommendations and the five fields of each prescription', function () {
    $prescription = PrescriptionEntity::fromPrimitives(
        id: '4f5a6b7c-8d9e-4f0a-8b1c-2d3e4f5a6b01',
        appointmentTrackingId: '3e4f5a6b-7c8d-4e9f-8a1b-2c3d4e5f6a7b',
        medication: 'Amoxicilina',
        dosage: '500 mg',
        durationDays: 7,
        dailyFrequency: 3,
        instructions: 'Tomar con alimentos',
        createdAt: '2026-09-10 11:00:00',
        updatedAt: '2026-09-10 11:00:00',
    );

    $data = json_decode(json_encode((new PortalPrescriptionsResource([
        'recommendations' => 'Cepillarse tres veces al día',
        'prescriptions' => [$prescription],
    ]))->toArray(Request::create('/'))), true);

    expect($data)->toBe([
        'recommendations' => 'Cepillarse tres veces al día',
        'prescriptions' => [[
            'medication' => 'Amoxicilina',
            'dosage' => '500 mg',
            'daily_frequency' => 3,
            'duration_days' => 7,
            'instructions' => 'Tomar con alimentos',
        ]],
    ]);
});

it('says null and an empty list when there is nothing to read', function () {
    $data = (new PortalPrescriptionsResource(['recommendations' => null, 'prescriptions' => []]))->toArray(Request::create('/'));

    expect(json_decode(json_encode($data), true))->toBe(['recommendations' => null, 'prescriptions' => []]);
});
