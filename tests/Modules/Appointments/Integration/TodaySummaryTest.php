<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

/*
 * Spec 020 (CA1, CA2, CA3, CA13, TM1, D25): GET /agenda/today-summary on Thursday 22 October 2026
 * at 11:40, with the day of design.md: 8 appointments, 3 completed, 4 to attend and 1 cancelled,
 * the next at 12:30. The doctor only ever receives their own, whatever the request asks for.
 */

const TODAY_SUMMARY_URL = '/api/v1/agenda/today-summary';

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->fakeTwilio();
});

/**
 * The 8 appointments of today split between two doctors, plus one yesterday and one tomorrow
 * that must stay out.
 *
 * @return array{daniela: UserModel, diego: UserModel, appointments: array<string, AppointmentModel>}
 */
function summaryDay(object $test): array
{
    return (function (): array {
        $daniela = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
        $diego = $this->createUserWithRole('doctor', ['first_name' => 'Diego', 'last_name' => 'Prueba']);
        $treatment = $this->createTreatment(['name' => 'Limpieza dental de prueba']);

        $book = function (string $time, string $status, UserModel $doctor, string $patient, string $date = '2026-10-22') use ($treatment): AppointmentModel {
            [$firstName, $lastName] = explode(' ', $patient);

            return $this->createAppointment([
                'date' => $date,
                'time' => $time,
                'status' => $status,
                'user_id' => $doctor->id,
                'treatment_id' => $treatment->id,
                'patient_id' => $this->createPatient(['first_name' => $firstName, 'last_name' => $lastName])->id,
            ]);
        };

        $appointments = [
            '17:30' => $book('17:30', 'asignada', $daniela, 'Sofía Prueba'),
            '09:00' => $book('09:00', 'completada', $diego, 'Laura Prueba'),
            '13:15' => $book('13:15', 'reprogramada', $daniela, 'Javier Prueba'),
            '10:30' => $book('10:30', 'cancelada', $diego, 'Marta Prueba'),
            '12:30' => $book('12:30', 'asignada', $diego, 'Paula Prueba'),
            '09:45' => $book('09:45', 'completada', $daniela, 'Andrés Prueba'),
            '16:00' => $book('16:00', 'asignada', $diego, 'Carmen Prueba'),
            '11:15' => $book('11:15', 'completada', $daniela, 'Tomás Prueba'),
        ];

        $book('12:00', 'asignada', $daniela, 'Ayer Prueba', '2026-10-21');
        $book('10:00', 'asignada', $diego, 'Mañana Prueba', '2026-10-23');

        return ['daniela' => $daniela, 'diego' => $diego, 'appointments' => $appointments];
    })->call($test);
}

/**
 * @param  list<array<string, mixed>>  $appointments
 * @return array<string, array<string, mixed>>
 */
function summaryByTime(array $appointments): array
{
    return collect($appointments)->keyBy('time')->all();
}

it('answers with the shape of the contract', function () {
    summaryDay($this);
    $this->actingAsAdmin();

    $this->getJson(TODAY_SUMMARY_URL)
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'date', 'now',
            'appointments' => ['*' => ['id', 'date', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'is_past', 'is_next', 'can' => ['complete', 'cancel']]],
            'counts' => ['total', 'completed', 'pending', 'cancelled'],
            'next' => ['id', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'can' => ['complete', 'cancel']],
            'after' => ['*' => ['time', 'patient_name']],
            'after_more',
        ]])
        ->assertJsonPath('data.date', '2026-10-22')
        ->assertJsonPath('data.now', '11:40');
});

it('lists the appointments of today by time with their patient, treatment, doctor and status', function () {
    summaryDay($this);
    $this->actingAsAdmin();

    $data = $this->getJson(TODAY_SUMMARY_URL)->assertOk()->json('data');
    $byTime = summaryByTime($data['appointments']);

    expect(array_column($data['appointments'], 'time'))->toBe(['09:00', '09:45', '10:30', '11:15', '12:30', '13:15', '16:00', '17:30'])
        ->and($byTime['12:30'])->toMatchArray([
            'date' => '2026-10-22',
            'status' => 'asignada',
            'patient_name' => 'Paula Prueba',
            'treatment_name' => 'Limpieza dental de prueba',
            'doctor_name' => 'Diego Prueba',
            'is_past' => false,
            'is_next' => true,
        ])
        ->and($byTime['10:30']['status'])->toBe('cancelada')
        ->and($byTime['11:15']['is_past'])->toBeTrue()
        ->and(array_keys(array_filter(array_column($data['appointments'], 'is_next', 'time'))))->toBe(['12:30']);
});

it('counts 8 appointments today: 3 completed, 4 to attend and 1 cancelled', function () {
    summaryDay($this);
    $this->actingAsAdmin();

    $this->getJson(TODAY_SUMMARY_URL)
        ->assertOk()
        ->assertJsonPath('data.counts', ['total' => 8, 'completed' => 3, 'pending' => 4, 'cancelled' => 1]);
});

it('gives the next appointment and the two after it, with how many more remain', function () {
    $day = summaryDay($this);
    $this->actingAsAdmin();

    $data = $this->getJson(TODAY_SUMMARY_URL)->assertOk()->json('data');

    expect($data['next'])->toMatchArray([
        'id' => $day['appointments']['12:30']->id,
        'time' => '12:30',
        'status' => 'asignada',
        'patient_name' => 'Paula Prueba',
        'treatment_name' => 'Limpieza dental de prueba',
        'doctor_name' => 'Diego Prueba',
    ])
        ->and($data['after'])->toBe([
            ['time' => '13:15', 'patient_name' => 'Javier Prueba'],
            ['time' => '16:00', 'patient_name' => 'Carmen Prueba'],
        ])
        ->and($data['after_more'])->toBe(1);
});

it('keeps an appointment to attend whose time has passed as the next one until it is completed (D25)', function () {
    $day = summaryDay($this);
    $overdue = $this->createAppointment([
        'date' => '2026-10-22',
        'time' => '11:30',
        'status' => 'asignada',
        'user_id' => $day['diego']->id,
        'patient_id' => $this->createPatient(['first_name' => 'Vencida', 'last_name' => 'Prueba'])->id,
    ]);
    $this->actingAsAdmin();

    $this->getJson(TODAY_SUMMARY_URL)
        ->assertOk()
        ->assertJsonPath('data.next.time', '11:30')
        ->assertJsonPath('data.next.patient_name', 'Vencida Prueba')
        ->assertJsonPath('data.after.0.time', '12:30');

    $overdue->update(['status' => 'completada']);

    $this->getJson(TODAY_SUMMARY_URL)
        ->assertOk()
        ->assertJsonPath('data.next.time', '12:30')
        ->assertJsonPath('data.counts.completed', 4);
});

it('answers a day without appointments with empty sections', function () {
    $this->actingAsAdmin();

    $this->getJson(TODAY_SUMMARY_URL)
        ->assertOk()
        ->assertJsonPath('data.appointments', [])
        ->assertJsonPath('data.counts', ['total' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0])
        ->assertJsonPath('data.next', null)
        ->assertJsonPath('data.after', [])
        ->assertJsonPath('data.after_more', 0);
});

it('gives the doctor only their own appointments of today, whatever filter the request sends (abuse)', function (array $query) {
    $day = summaryDay($this);
    $this->actingAs($day['daniela'], 'sanctum');

    $query = array_map(fn (string $value): string => $value === ':other' ? $day['diego']->id : $value, $query);
    $data = $this->getJson(TODAY_SUMMARY_URL.($query === [] ? '' : '?'.http_build_query($query)))->assertOk()->json('data');

    expect(array_column($data['appointments'], 'time'))->toBe(['09:45', '11:15', '13:15', '17:30'])
        ->and(array_unique(array_column($data['appointments'], 'doctor_name')))->toBe(['Daniela Prueba'])
        ->and($data['counts'])->toBe(['total' => 4, 'completed' => 2, 'pending' => 2, 'cancelled' => 0])
        ->and($data['next']['time'])->toBe('13:15')
        ->and($data['after'])->toBe([['time' => '17:30', 'patient_name' => 'Sofía Prueba']])
        ->and($data['after_more'])->toBe(0);
})->with([
    'sin filtros' => [[]],
    'user_id de otro doctor' => [['user_id' => ':other']],
    'doctor_id de otro doctor' => [['doctor_id' => ':other']],
]);

it('tells the administrator they may complete and cancel the appointments to attend', function () {
    summaryDay($this);
    $this->actingAsAdmin();

    $data = $this->getJson(TODAY_SUMMARY_URL)->assertOk()->json('data');
    $byTime = summaryByTime($data['appointments']);

    expect($byTime['12:30']['can'])->toBe(['complete' => true, 'cancel' => true])
        ->and($byTime['13:15']['can'])->toBe(['complete' => true, 'cancel' => true])
        ->and($byTime['09:00']['can'])->toBe(['complete' => false, 'cancel' => false])
        ->and($byTime['10:30']['can'])->toBe(['complete' => false, 'cancel' => false])
        ->and($data['next']['can'])->toBe(['complete' => true, 'cancel' => true]);
});

it('tells the assistant they may cancel but not complete', function () {
    summaryDay($this);
    $this->actingAsNonAdminUser('Asistente');

    $data = $this->getJson(TODAY_SUMMARY_URL)->assertOk()->json('data');
    $byTime = summaryByTime($data['appointments']);

    expect($data['appointments'])->toHaveCount(8)
        ->and($byTime['12:30']['can'])->toBe(['complete' => false, 'cancel' => true])
        ->and($byTime['09:00']['can'])->toBe(['complete' => false, 'cancel' => false])
        ->and($data['next']['can'])->toBe(['complete' => false, 'cancel' => true]);
});

it('tells the doctor they may complete their appointments to attend but not cancel them', function () {
    $day = summaryDay($this);
    $this->actingAs($day['daniela'], 'sanctum');

    $data = $this->getJson(TODAY_SUMMARY_URL)->assertOk()->json('data');
    $byTime = summaryByTime($data['appointments']);

    expect($byTime['13:15']['can'])->toBe(['complete' => true, 'cancel' => false])
        ->and($byTime['09:45']['can'])->toBe(['complete' => false, 'cancel' => false])
        ->and($data['next']['can'])->toBe(['complete' => true, 'cancel' => false]);
});
