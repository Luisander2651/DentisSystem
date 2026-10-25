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
})->skip('020: pendiente de T038');

it('gives the administrator only the completed appointments without a follow-up', function () {
    $days = withoutTrackingDays($this);
    $this->actingAsAdmin();

    $data = $this->getJson(WITHOUT_TRACKING_URL)->assertOk()->json('data');

    expect($data['total'])->toBe(6)
        ->and(array_column($data['items'], 'id'))->toEqualCanonicalizing($days['pending'])
        ->and(array_unique(array_column($data['items'], 'status')))->toBe(['completada'])
        ->and(array_unique(array_map(fn (array $item): string => json_encode($item['can']), $data['items'])))->toBe([json_encode(['register' => true])]);
})->skip('020: pendiente de T038');

it('gives at most 10 of them and the total of all (TM8)', function () {
    $this->actingAsAdmin();

    foreach (range(1, 12) as $day) {
        $this->createAppointment(['date' => sprintf('2026-10-%02d', $day), 'time' => '10:00', 'status' => 'completada']);
    }

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertOk()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('data.total', 12);
})->skip('020: pendiente de T038');

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
])->skip('020: pendiente de T038');

it('refuses the assistant (abuse)', function () {
    withoutTrackingDays($this);
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertForbidden()
        ->assertJsonMissingPath('data');
})->skip('020: pendiente de T038');

it('answers an empty list with a total of 0', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'time' => '10:00', 'status' => 'completada']);
    withoutTrackingFollowUp($appointment);

    $this->getJson(WITHOUT_TRACKING_URL)
        ->assertOk()
        ->assertExactJson(['data' => ['items' => [], 'total' => 0]]);
})->skip('020: pendiente de T038');
