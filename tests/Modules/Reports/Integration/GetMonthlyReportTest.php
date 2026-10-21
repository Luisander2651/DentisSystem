<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA1–CA7 and CA9: the report of a month in the API, with the figures of July 2026 of
 * design.md and the contract of plan.md.
 */

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

it('gives the report of July 2026 with the figures of the design and the shape of the contract', function () {
    $this->seedJuly2026();

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'month' => '2026-07',
            'label' => 'julio de 2026',
            'short_label' => 'julio',
            'previous_label' => 'junio de 2026',
            'previous_short_label' => 'junio',
            'is_current' => false,
            'data_until' => null,
            'appointments' => [
                'total' => ['value' => 8, 'previous' => 0, 'difference' => 8, 'trend' => 'favorable'],
                'completed' => ['value' => 2, 'previous' => 0, 'difference' => 2, 'trend' => 'favorable'],
                'cancelled' => ['value' => 1, 'previous' => 0, 'difference' => 1, 'trend' => 'unfavorable'],
                'unclosed' => ['value' => 5, 'previous' => 0, 'difference' => 5, 'trend' => 'unfavorable'],
                'rescheduled_pending' => ['value' => 0, 'previous' => 0, 'difference' => 0, 'trend' => 'neutral'],
                'assigned_pending' => ['value' => 0, 'previous' => 0, 'difference' => 0, 'trend' => 'neutral'],
            ],
            'rates' => [
                'attendance' => ['value' => 28.6, 'previous' => null, 'difference' => null, 'numerator' => 2, 'denominator' => 7, 'trend' => 'none'],
                'cancellation' => ['value' => 12.5, 'previous' => null, 'difference' => null, 'numerator' => 1, 'denominator' => 8, 'trend' => 'none'],
            ],
            'patients' => [
                'new' => ['value' => 0, 'previous' => 0, 'difference' => 0, 'trend' => 'neutral'],
                'active' => ['value' => 2, 'as_of' => '2026-10-19'],
            ],
            'treatments' => [
                ['name' => 'Limpieza dental de prueba', 'appointments' => 3, 'is_top' => true],
                ['name' => 'Ortodoncia de prueba', 'appointments' => 2, 'is_top' => false],
                ['name' => 'Resina de prueba', 'appointments' => 2, 'is_top' => false],
            ],
            'staff' => [
                ['name' => 'Daniela Prueba', 'appointments' => 5, 'completed' => 2, 'is_top' => true],
                ['name' => 'Diego Prueba', 'appointments' => 2, 'completed' => 0, 'is_top' => false],
            ],
            'demand' => [
                'hours' => [9, 10, 11, 12, 13, 14, 15, 16],
                'matrix' => [
                    [1, 0, 1, 0, 0, 0, 0],
                    [0, 1, 0, 0, 0, 0, 0],
                    [0, 0, 1, 0, 0, 0, 0],
                    [1, 0, 1, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 1, 0, 0, 0, 0],
                ],
                'by_weekday' => [2, 1, 4, 0, 0, 0, 0],
                'by_hour' => [2, 1, 1, 2, 0, 0, 0, 1],
                'top_weekdays' => [2],
                'top_hours' => [9, 12],
                'total' => 7,
            ],
        ]]);
});

it('counts a rescheduled appointment in the month of its current date, not the one it left', function () {
    $this->createAppointment(['date' => '2026-07-20', 'status' => 'reprogramada']);

    $this->getJson($this->monthlyReportUrl('2026-07'))->assertJsonPath('data.appointments.total.value', 1);
    $this->getJson($this->monthlyReportUrl('2026-06'))->assertJsonPath('data.appointments.total.value', 0);
});

it('counts an appointment on the last evening of the month in that month, in the time of the clinic', function () {
    $this->createAppointment(['date' => '2026-07-31', 'time' => '23:30']);

    $this->getJson($this->monthlyReportUrl('2026-07'))->assertJsonPath('data.appointments.total.value', 1);
    $this->getJson($this->monthlyReportUrl('2026-08'))->assertJsonPath('data.appointments.total.value', 0);
});

it('leaves cancelled appointments out of treatments, staff and the week', function () {
    $treatment = $this->createTreatment(['name' => 'Solo cancelada']);
    $doctor = $this->createUserWithRole('doctor', ['first_name' => 'Carla', 'last_name' => 'Cancelada']);
    $this->createAppointment(['date' => '2026-07-09', 'treatment_id' => $treatment->id, 'user_id' => $doctor->id, 'status' => 'cancelada']);

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertJsonPath('data.appointments.cancelled.value', 1)
        ->assertJsonPath('data.treatments', [])
        ->assertJsonPath('data.staff', [])
        ->assertJsonPath('data.demand.total', 0)
        ->assertJsonPath('data.demand.hours', []);
});

it('orders tied treatments and staff by name and lists no treatment without appointments', function () {
    $this->createTreatment(['name' => 'Sin citas']);
    $resin = $this->createTreatment(['name' => 'Resina de prueba']);
    $crown = $this->createTreatment(['name' => 'Corona de prueba']);
    $zoe = $this->createUserWithRole('doctor', ['first_name' => 'Zoe', 'last_name' => 'Prueba']);
    $ana = $this->createUserWithRole('doctor', ['first_name' => 'Ana', 'last_name' => 'Prueba']);
    $this->createAppointment(['date' => '2026-07-06', 'treatment_id' => $resin->id, 'user_id' => $zoe->id]);
    $this->createAppointment(['date' => '2026-07-07', 'treatment_id' => $crown->id, 'user_id' => $ana->id]);

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertJsonPath('data.treatments.0.name', 'Corona de prueba')
        ->assertJsonPath('data.treatments.1.name', 'Resina de prueba')
        ->assertJsonCount(2, 'data.treatments')
        ->assertJsonPath('data.staff.0.name', 'Ana Prueba')
        ->assertJsonPath('data.staff.1.name', 'Zoe Prueba')
        ->assertJsonPath('data.staff.0.is_top', true)
        ->assertJsonPath('data.staff.1.is_top', true);
});

it('shows an inactive doctor with appointments in the month and hides one with only cancelled ones', function () {
    $inactive = $this->createUserWithRole('doctor', ['first_name' => 'Ines', 'last_name' => 'Inactiva', 'status' => 'inactive']);
    $onlyCancelled = $this->createUserWithRole('doctor', ['first_name' => 'Carla', 'last_name' => 'Cancelada']);
    $this->createAppointment(['date' => '2026-07-06', 'user_id' => $inactive->id, 'status' => 'completada']);
    $this->createAppointment(['date' => '2026-07-07', 'user_id' => $onlyCancelled->id, 'status' => 'cancelada']);

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertJsonPath('data.staff', [['name' => 'Ines Inactiva', 'appointments' => 1, 'completed' => 1, 'is_top' => true]]);
});

it('counts new patients by the day they were registered, against the month before, and the active ones as of today', function () {
    $this->patientCreatedOn('2026-06-30 23:00:00');
    $this->patientCreatedOn('2026-07-01 08:00:00');
    $this->patientCreatedOn('2026-07-31 20:00:00', 'inactive');
    $this->patientCreatedOn('2026-08-01 09:00:00');

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertJsonPath('data.patients.new', ['value' => 2, 'previous' => 1, 'difference' => 1, 'trend' => 'favorable'])
        ->assertJsonPath('data.patients.active', ['value' => 3, 'as_of' => '2026-10-19']);
});

it('compares the rates in points, rounding half up', function () {
    foreach (range(1, 16) as $day) {
        $this->createAppointment(['date' => sprintf('2026-07-%02d', $day), 'status' => $day === 1 ? 'cancelada' : 'completada']);
    }

    $this->createAppointment(['date' => '2026-06-10', 'status' => 'completada']);
    $this->createAppointment(['date' => '2026-06-11', 'status' => 'asignada']);

    $this->getJson($this->monthlyReportUrl('2026-07'))
        ->assertJsonPath('data.rates.cancellation.value', 6.3)
        ->assertJsonPath('data.rates.attendance.value', 100)
        ->assertJsonPath('data.rates.attendance.previous', 50)
        ->assertJsonPath('data.rates.attendance.difference', 50)
        ->assertJsonPath('data.rates.attendance.trend', 'favorable')
        ->assertJsonPath('data.rates.cancellation.difference', 6.3)
        ->assertJsonPath('data.rates.cancellation.trend', 'unfavorable');
});

it('has no rates, lists or week in a month without appointments', function () {
    $this->getJson($this->monthlyReportUrl('2026-08'))
        ->assertOk()
        ->assertJsonPath('data.appointments.total.value', 0)
        ->assertJsonPath('data.rates.attendance.value', null)
        ->assertJsonPath('data.rates.cancellation.value', null)
        ->assertJsonPath('data.treatments', [])
        ->assertJsonPath('data.staff', [])
        ->assertJsonPath('data.demand.hours', [])
        ->assertJsonPath('data.demand.matrix', [])
        ->assertJsonPath('data.demand.top_weekdays', [])
        ->assertJsonPath('data.demand.top_hours', []);
});

it('marks the month in course with its data until today and counts the appointments of today as pending', function () {
    $this->createAppointment(['date' => '2026-10-19', 'time' => '08:00', 'status' => 'asignada']);
    $this->createAppointment(['date' => '2026-10-19', 'time' => '09:00', 'status' => 'reprogramada']);
    $this->createAppointment(['date' => '2026-10-18', 'status' => 'asignada']);
    $this->createAppointment(['date' => '2026-10-25', 'status' => 'asignada']);

    $this->getJson($this->monthlyReportUrl('2026-10'))
        ->assertOk()
        ->assertJsonPath('data.is_current', true)
        ->assertJsonPath('data.data_until', '2026-10-19')
        ->assertJsonPath('data.appointments.assigned_pending.value', 2)
        ->assertJsonPath('data.appointments.rescheduled_pending.value', 1)
        ->assertJsonPath('data.appointments.unclosed.value', 1);
});

it('counts as attendance only the appointments completed on a day already gone, in the month in course (R1)', function () {
    $this->createAppointment(['date' => '2026-10-10', 'status' => 'asignada']);
    $this->createAppointment(['date' => '2026-10-19', 'status' => 'completada']);
    $this->createAppointment(['date' => '2026-10-22', 'status' => 'completada']);

    $this->getJson($this->monthlyReportUrl('2026-10'))
        ->assertOk()
        ->assertJsonPath('data.appointments.completed.value', 2)
        ->assertJsonPath('data.rates.attendance.numerator', 0)
        ->assertJsonPath('data.rates.attendance.denominator', 1)
        ->assertJsonPath('data.rates.attendance.value', 0);
});

it('keeps the month of the clinic in course on its last evening, when it is already the next month in UTC (R2)', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-31 20:00:00', 'America/Mexico_City'));

    $this->getJson($this->monthlyReportUrl('2026-10'))
        ->assertOk()
        ->assertJsonPath('data.is_current', true)
        ->assertJsonPath('data.data_until', '2026-10-31');
});

it('starts the next month of the clinic after its midnight, with the patients registered then (R2)', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 00:30:00', 'America/Mexico_City'));
    $this->createPatient();

    $this->getJson($this->monthlyReportUrl('2026-11'))
        ->assertOk()
        ->assertJsonPath('data.is_current', true)
        ->assertJsonPath('data.data_until', '2026-11-01')
        ->assertJsonPath('data.patients.new.value', 1);
    $this->getJson($this->monthlyReportUrl('2026-10'))->assertJsonPath('data.patients.new.value', 0);
});
