<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * Spec 020 (CA7, CA13, CA14, TM1, TM3, TM8): GET /agenda/without-tracking on Thursday 22 October
 * 2026 at 11:40. Only completed appointments without a clinical follow-up, at most 10 and the
 * total of all. The doctor only ever receives their own; the assistant is refused.
 */

const WITHOUT_TRACKING_URL = '/api/v1/agenda/without-tracking';

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
});

function withoutTrackingFollowUp(AppointmentModel $appointment): void
{
    AppointmentTrackingModel::query()->create([
        'id' => (string) Str::uuid(),
        'reason' => 'Revisión de una muela',
        'symptoms' => ['sensibilidad'],
        'diagnosis' => 'Caries superficial',
        'procedure_performed' => 'Resina',
        'observations' => null,
        'recommendations' => null,
        'appointment_id' => $appointment->id,
    ]);
}

/**
 * 6 completed appointments without a follow-up (2 of Daniela, 4 of Diego), plus the ones that
 * must stay out: completed with a follow-up, assigned, rescheduled and cancelled.
 *
 * @return array{daniela: UserModel, diego: UserModel, pending: list<string>, daniela_pending: list<string>}
 */
function withoutTrackingDays(object $test): array
{
    return (function (): array {
        $daniela = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
        $diego = $this->createUserWithRole('doctor', ['first_name' => 'Diego', 'last_name' => 'Prueba']);

        $book = fn (string $date, string $status, UserModel $doctor): AppointmentModel => $this->createAppointment([
            'date' => $date,
            'time' => '10:00',
            'status' => $status,
            'user_id' => $doctor->id,
        ]);

        $danielaPending = [$book('2026-10-21', 'completada', $daniela)->id, $book('2026-10-15', 'completada', $daniela)->id];
        $diegoPending = [
            $book('2026-10-22', 'completada', $diego)->id,
            $book('2026-10-20', 'completada', $diego)->id,
            $book('2026-10-16', 'completada', $diego)->id,
            $book('2026-10-14', 'completada', $diego)->id,
        ];

        withoutTrackingFollowUp($book('2026-10-21', 'completada', $daniela));
        withoutTrackingFollowUp($book('2026-10-19', 'completada', $diego));
        $book('2026-10-20', 'asignada', $daniela);
        $book('2026-10-20', 'reprogramada', $diego);
        $book('2026-10-19', 'cancelada', $diego);

        return [
            'daniela' => $daniela,
            'diego' => $diego,
            'pending' => [...$danielaPending, ...$diegoPending],
            'daniela_pending' => $danielaPending,
        ];
    })->call($test);
}

it('answers with the shape of the contract', function () {
    withoutTrackingDays($this);
    $this->actingAsAdmin();

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'items' => ['*' => ['id', 'date', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'can' => ['register']]],
            'total',
        ]]);
});

it('gives the administrator only the completed appointments without a follow-up', function () {
    $days = withoutTrackingDays($this);
    $this->actingAsAdmin();

    $data = $this->getJson(WITHOUT_TRACKING_URL)->assertOk()->json('data');

    expect($data['total'])->toBe(6)
        ->and(array_column($data['items'], 'id'))->toEqualCanonicalizing($days['pending'])
        ->and(array_unique(array_column($data['items'], 'status')))->toBe(['completada'])
        ->and(array_unique(array_map(fn (array $item): string => json_encode($item['can']), $data['items'])))->toBe([json_encode(['register' => true])]);
});

it('orders them from the most recent to the oldest, by date and then by time (CA7)', function () {
    $this->actingAsAdmin();

    $book = fn (string $date, string $time): string => $this->createAppointment(['date' => $date, 'time' => $time, 'status' => 'completada'])->id;
    $earlyOfTheDay = $book('2026-10-21', '09:00');
    $oldest = $book('2026-10-14', '08:00');
    $lateOfTheDay = $book('2026-10-21', '16:00');
    $dayBefore = $book('2026-10-20', '12:00');

    $ids = array_column($this->getJson(WITHOUT_TRACKING_URL)->assertOk()->json('data.items'), 'id');

    expect($ids)->toBe([$lateOfTheDay, $earlyOfTheDay, $dayBefore, $oldest]);
});

it('gives at most 10 of them and the total of all (TM8)', function () {
    $this->actingAsAdmin();

    foreach (range(1, 12) as $day) {
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '10:00', 'status' => 'completada']);
    }

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertOk()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('data.total', 12);
});

it('gives the doctor only their own, whatever filter the request sends (abuse)', function (array $query) {
    $days = withoutTrackingDays($this);
    $this->actingAs($days['daniela'], 'sanctum');

    $query = array_map(fn (string $value): string => $value === ':other' ? $days['diego']->id : $value, $query);
    $data = $this->getJson(WITHOUT_TRACKING_URL.($query === [] ? '' : '?'.http_build_query($query)))->assertOk()->json('data');

    expect($data['total'])->toBe(2)
        ->and(array_column($data['items'], 'id'))->toEqualCanonicalizing($days['daniela_pending'])
        ->and(array_unique(array_column($data['items'], 'doctor_name')))->toBe(['Daniela Prueba']);
})->with([
    'sin filtros' => [[]],
    'user_id de otro doctor' => [['user_id' => ':other']],
    'doctor_id de otro doctor' => [['doctor_id' => ':other']],
]);

it('refuses the assistant (abuse)', function () {
    withoutTrackingDays($this);
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertForbidden()
        ->assertJsonMissingPath('data');
});

it('answers an empty list with a total of 0', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'time' => '10:00', 'status' => 'completada']);
    withoutTrackingFollowUp($appointment);

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertOk()
        ->assertExactJson([
            'data' => ['items' => [], 'total' => 0],
            'meta' => ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1, 'from' => null, 'to' => null],
        ]);
});

/*
 * Spec 028 (CA2, CA7, CA9, CA14): the section is paged by ten; the rest are on the next pages.
 */
it('gives the second page with the ones left, the total and its meta', function () {
    $this->actingAsAdmin();

    foreach (range(1, 12) as $day) {
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '10:00', 'status' => 'completada']);
    }

    $response = $this->getJson(WITHOUT_TRACKING_URL.'?page=2')->assertOk();

    expect(array_column($response->json('data.items'), 'date'))->toBe(['2026-10-02', '2026-10-01'])
        ->and($response->json('data.total'))->toBe(12)
        ->and($response->json('meta'))->toBe(['page' => 2, 'per_page' => 10, 'total' => 12, 'last_page' => 2, 'from' => 11, 'to' => 12]);
});

it('keeps ten per page whatever size the request asks for', function () {
    $this->actingAsAdmin();

    foreach (range(1, 12) as $day) {
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '10:00', 'status' => 'completada']);
    }

    $this->getJson(WITHOUT_TRACKING_URL.'?per_page=50')
        ->assertOk()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('meta.per_page', 10);
});

it('gives every appointment exactly once across the pages when they share the date and the time', function () {
    $this->actingAsAdmin();

    $ids = array_map(fn (): string => $this->createAppointment(['date' => '2026-10-10', 'time' => '10:00', 'status' => 'completada'])->id, range(1, 15));

    $seen = [
        ...array_column($this->getJson(WITHOUT_TRACKING_URL.'?page=1')->json('data.items'), 'id'),
        ...array_column($this->getJson(WITHOUT_TRACKING_URL.'?page=2')->json('data.items'), 'id'),
    ];

    expect($seen)->toBe(collect($ids)->sortDesc()->values()->all());
});

it('gives the doctor only their own on every page', function () {
    $daniela = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
    $diego = $this->createUserWithRole('doctor', ['first_name' => 'Diego', 'last_name' => 'Prueba']);

    foreach (range(1, 12) as $day) {
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '10:00', 'status' => 'completada', 'user_id' => $diego->id]);
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '11:00', 'status' => 'completada', 'user_id' => $daniela->id]);
    }

    $this->actingAs($diego, 'sanctum');
    $second = $this->getJson(WITHOUT_TRACKING_URL.'?page=2')->assertOk()->json('data');

    expect($second['total'])->toBe(12)
        ->and($second['items'])->toHaveCount(2)
        ->and(array_unique(array_column($second['items'], 'doctor_name')))->toBe(['Diego Prueba']);
});

it('rejects a page that is not a whole number above zero with 422', function (string $page) {
    $this->actingAsAdmin();

    $this->getJson(WITHOUT_TRACKING_URL.'?page='.$page)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);
})->with(['abc', '0']);
