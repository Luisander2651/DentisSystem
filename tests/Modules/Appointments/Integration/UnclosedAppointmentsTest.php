<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

/*
 * Spec 020 (CA4, CA5, CA6, CA13, TM1, TM8): GET /agenda/unclosed on Thursday 22 October 2026 at
 * 11:40. Only appointments of earlier days still assigned or rescheduled, from the most recent to
 * the oldest, at most 10 and the total of all 15. The doctor only ever receives their own.
 */

const UNCLOSED_URL = '/api/v1/agenda/unclosed';

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->fakeTwilio();
});

/**
 * 15 unclosed appointments of earlier days (4 of Daniela, 11 of Diego), plus the ones that must
 * stay out: today's, and earlier ones already completed or cancelled.
 *
 * @return array{daniela: UserModel, diego: UserModel}
 */
function unclosedDays(object $test): array
{
    return (function (): array {
        $daniela = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
        $diego = $this->createUserWithRole('doctor', ['first_name' => 'Diego', 'last_name' => 'Prueba']);
        $treatment = $this->createTreatment(['name' => 'Limpieza dental de prueba']);

        $book = fn (string $date, string $time, string $status, UserModel $doctor, string $patient = 'Paula Prueba') => $this->createAppointment([
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'user_id' => $doctor->id,
            'treatment_id' => $treatment->id,
            'patient_id' => $this->createPatient(['first_name' => explode(' ', $patient)[0], 'last_name' => explode(' ', $patient)[1]])->id,
        ]);

        $book('2026-10-21', '10:00', 'asignada', $daniela);
        $book('2026-10-21', '16:00', 'reprogramada', $diego, 'Carmen Prueba');
        $book('2026-10-20', '09:00', 'asignada', $diego);
        $book('2026-10-20', '12:00', 'asignada', $diego);
        $book('2026-10-19', '11:00', 'asignada', $daniela);
        $book('2026-10-16', '09:30', 'asignada', $diego);
        $book('2026-10-16', '17:00', 'reprogramada', $diego);
        $book('2026-10-15', '10:00', 'asignada', $diego);
        $book('2026-10-14', '10:00', 'asignada', $daniela);
        $book('2026-10-13', '10:00', 'asignada', $diego);
        $book('2026-10-12', '10:00', 'asignada', $diego);
        $book('2026-10-09', '10:00', 'asignada', $daniela);
        $book('2026-10-08', '10:00', 'asignada', $diego);
        $book('2026-10-07', '10:00', 'asignada', $diego);
        $book('2026-10-06', '10:00', 'asignada', $diego);

        $book('2026-10-22', '09:00', 'asignada', $diego);
        $book('2026-10-22', '12:30', 'asignada', $daniela);
        $book('2026-10-21', '11:00', 'completada', $diego);
        $book('2026-10-20', '15:00', 'cancelada', $daniela);

        return ['daniela' => $daniela, 'diego' => $diego];
    })->call($test);
}

/**
 * @param  list<array<string, mixed>>  $items
 * @return list<string>
 */
function unclosedMoments(array $items): array
{
    return array_map(fn (array $item): string => $item['date'].' '.$item['time'], $items);
}

it('answers with the shape of the contract', function () {
    unclosedDays($this);
    $this->actingAsAdmin();

    $this->getJson(UNCLOSED_URL)
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'items' => ['*' => ['id', 'date', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'day_label', 'can' => ['complete', 'cancel']]],
            'total',
        ]]);
})->skip('020: pendiente de T035');

it('gives the 10 most recent unclosed appointments of earlier days and the total of 15 (TM8)', function () {
    unclosedDays($this);
    $this->actingAsAdmin();

    $data = $this->getJson(UNCLOSED_URL)->assertOk()->json('data');

    expect($data['total'])->toBe(15)
        ->and(unclosedMoments($data['items']))->toBe([
            '2026-10-21 16:00', '2026-10-21 10:00',
            '2026-10-20 12:00', '2026-10-20 09:00',
            '2026-10-19 11:00',
            '2026-10-16 17:00', '2026-10-16 09:30',
            '2026-10-15 10:00', '2026-10-14 10:00', '2026-10-13 10:00',
        ])
        ->and(array_unique(array_column($data['items'], 'status')))->toEqualCanonicalizing(['asignada', 'reprogramada']);
})->skip('020: pendiente de T035');

it('leaves out the appointments of today and the ones already completed or cancelled', function () {
    unclosedDays($this);
    $this->actingAsAdmin();

    $moments = unclosedMoments($this->getJson(UNCLOSED_URL)->assertOk()->json('data.items'));

    expect($moments)->not->toContain('2026-10-22 09:00')
        ->and($moments)->not->toContain('2026-10-22 12:30')
        ->and($moments)->not->toContain('2026-10-21 11:00')
        ->and($moments)->not->toContain('2026-10-20 15:00');
})->skip('020: pendiente de T035');

it('labels each day, yesterday included', function () {
    unclosedDays($this);
    $this->actingAsAdmin();

    $items = $this->getJson(UNCLOSED_URL)->assertOk()->json('data.items');
    $labels = array_column($items, 'day_label', 'date');

    expect($labels['2026-10-21'])->toBe('Ayer · miércoles 21')
        ->and($labels['2026-10-20'])->toBe('Martes 20')
        ->and($labels['2026-10-19'])->toBe('Lunes 19')
        ->and($items[0])->toMatchArray([
            'status' => 'reprogramada',
            'patient_name' => 'Carmen Prueba',
            'treatment_name' => 'Limpieza dental de prueba',
            'doctor_name' => 'Diego Prueba',
        ]);
})->skip('020: pendiente de T035');

it('gives the doctor only their own unclosed appointments, whatever filter the request sends (abuse)', function (array $query) {
    $days = unclosedDays($this);
    $this->actingAs($days['daniela'], 'sanctum');

    $query = array_map(fn (string $value): string => $value === ':other' ? $days['diego']->id : $value, $query);
    $data = $this->getJson(UNCLOSED_URL.($query === [] ? '' : '?'.http_build_query($query)))->assertOk()->json('data');

    expect($data['total'])->toBe(4)
        ->and(unclosedMoments($data['items']))->toBe(['2026-10-21 10:00', '2026-10-19 11:00', '2026-10-14 10:00', '2026-10-09 10:00'])
        ->and(array_unique(array_column($data['items'], 'doctor_name')))->toBe(['Daniela Prueba']);
})->with([
    'sin filtros' => [[]],
    'user_id de otro doctor' => [['user_id' => ':other']],
    'doctor_id de otro doctor' => [['doctor_id' => ':other']],
])->skip('020: pendiente de T035');

it('answers an empty list with a total of 0', function () {
    $this->actingAsAdmin();
    $this->createAppointment(['date' => '2026-10-21', 'time' => '10:00', 'status' => 'completada']);

    $this->getJson(UNCLOSED_URL)
        ->assertOk()
        ->assertExactJson(['data' => ['items' => [], 'total' => 0]]);
})->skip('020: pendiente de T035');

it('tells each role what it may do with an unclosed appointment', function (string $actor, array $can) {
    $days = unclosedDays($this);

    match ($actor) {
        'administrador' => $this->actingAsAdmin(),
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'doctor' => $this->actingAs($days['daniela'], 'sanctum'),
    };

    $items = $this->getJson(UNCLOSED_URL)->assertOk()->json('data.items');

    expect(array_unique(array_map(fn (array $item): string => json_encode($item['can']), $items)))->toBe([json_encode($can)]);
})->with([
    'administrador' => ['administrador', ['complete' => true, 'cancel' => true]],
    'asistente' => ['asistente', ['complete' => false, 'cancel' => true]],
    'doctor' => ['doctor', ['complete' => true, 'cancel' => false]],
])->skip('020: pendiente de T035');
