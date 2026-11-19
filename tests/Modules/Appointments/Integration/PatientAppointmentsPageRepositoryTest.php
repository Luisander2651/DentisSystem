<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 028 (CA2, CA7): the appointments of one patient are read one page at a time, the most
 * recent first, and never those of another patient.
 */
function patientAppointmentsPageIds(array $appointments): array
{
    return array_map(fn (AppointmentEntity $appointment): string => $appointment->Id()->value, $appointments);
}

beforeEach(function () {
    $this->treatmentId = $this->createTreatment()->id;
    $this->doctorId = $this->createUserWithRole('Doctor')->id;
    $this->patient = $this->createPatient();
    $this->seedHistory = function (string $patientId, int $count, ?string $date = null): void {
        foreach (range(1, $count) as $number) {
            $this->createAppointment([
                'patient_id' => $patientId,
                'treatment_id' => $this->treatmentId,
                'user_id' => $this->doctorId,
                'date' => $date ?? sprintf('2026-01-%02d', $number),
                'time' => $date === null ? '10:00' : sprintf('%02d:00', 8 + $number % 10),
                'status' => 'completada',
            ]);
        }
    };
});

it('reads the third page of twenty-five appointments with the five left, most recent first', function () {
    ($this->seedHistory)($this->patient->id, 25);

    $first = app(AppointmentsService::class)->findPageByPatient(new PatientId($this->patient->id), PageRequest::create(1, 10));
    $third = app(AppointmentsService::class)->findPageByPatient(new PatientId($this->patient->id), PageRequest::create(3, 10));

    expect($first->items[0]->Date()->value)->toBe('2026-01-25')
        ->and($third->items)->toHaveCount(5)
        ->and($third->total)->toBe(25)
        ->and($third->items[4]->Date()->value)->toBe('2026-01-01');
});

it('orders the appointments of one day by time, the latest first', function () {
    ($this->seedHistory)($this->patient->id, 3, '2026-02-10');

    $page = app(AppointmentsService::class)->findPageByPatient(new PatientId($this->patient->id), PageRequest::create(1, 10));

    expect(array_map(fn (AppointmentEntity $appointment): string => substr($appointment->Time()->value, 0, 5), $page->items))
        ->toBe(['11:00', '10:00', '09:00']);
});

it('gives every appointment exactly once across the pages when they share the date and the time', function () {
    foreach (range(1, 25) as $number) {
        $this->createAppointment([
            'patient_id' => $this->patient->id,
            'treatment_id' => $this->treatmentId,
            'user_id' => $this->doctorId,
            'date' => '2026-03-01',
            'time' => '10:00',
        ]);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...patientAppointmentsPageIds(app(AppointmentsService::class)
            ->findPageByPatient(new PatientId($this->patient->id), PageRequest::create($number, 10))->items)];
    }

    expect($seen)->toBe(AppointmentModel::query()->where('patient_id', $this->patient->id)->pluck('id')->sortDesc()->values()->all());
});

it('never gives the appointments of another patient', function () {
    $other = $this->createPatient();
    ($this->seedHistory)($this->patient->id, 3);
    ($this->seedHistory)($other->id, 4);

    $page = app(AppointmentsService::class)->findPageByPatient(new PatientId($this->patient->id), PageRequest::create(1, 10));

    expect($page->total)->toBe(3)
        ->and(array_unique(array_map(fn (AppointmentEntity $appointment): string => $appointment->PatientId()->value, $page->items)))
        ->toBe([$this->patient->id]);
});

it('keeps the list by status and date whole', function () {
    ($this->seedHistory)($this->patient->id, 15);

    expect(app(AppointmentsService::class)->findByStatusAndDate(null, null, new PatientId($this->patient->id)))->toHaveCount(15);
});
