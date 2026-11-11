<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetPatientNextAppointmentDTO;
use App\Modules\Appointments\Aplication\UseCases\GetPatientNextAppointmentUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use Eris\Generator;
use Illuminate\Support\Str;
use Tests\Support\UsesEris;
use Tests\TestCase;

uses(TestCase::class, UsesEris::class);

/*
 * Spec 025 (CA1, CA2): for any set of appointments of a patient, the next one is open, of today
 * or later, and no other open appointment from today on comes before it; and there is a next one
 * exactly when some open appointment falls on today or later.
 */

const NEXT_PROPERTY_TODAY = '2026-09-17';

function nextPropertyAuthorization(): AuthorizationServiceInterface
{
    return new class implements AuthorizationServiceInterface
    {
        public function assertCan(string $permission): void
        {
            throw AuthorizationException::forbidden($permission);
        }

        public function can(string $permission): bool
        {
            return false;
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            return '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f';
        }
    };
}

/**
 * @param  list<array{0: int, 1: int, 2: string}>  $lines  days from today, minutes after midnight and status
 * @return list<AppointmentEntity>
 */
function nextPropertyAppointments(array $lines): array
{
    return array_map(fn (array $line): AppointmentEntity => AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: (new DateTimeImmutable(NEXT_PROPERTY_TODAY))->modify("{$line[0]} days")->format('Y-m-d'),
        time: sprintf('%02d:%02d', intdiv($line[1], 60), $line[1] % 60),
        whatsappReminder: false,
        status: $line[2],
        treatmentId: '1',
        userId: (string) Str::uuid(),
        patientId: '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
        treatmentName: null,
        userFirstName: null,
        userLastName: null,
        patientName: null,
        treatmentTime: null,
        createdAt: '2026-09-01 09:00:00',
        updatedAt: '2026-09-01 09:00:00',
    ), $lines);
}

function nextPropertyIsOpenFromToday(AppointmentEntity $appointment): bool
{
    return in_array($appointment->Status()->value, ['asignada', 'reprogramada'], true)
        && $appointment->Date()->value >= NEXT_PROPERTY_TODAY;
}

function nextPropertyKey(AppointmentEntity $appointment): string
{
    return $appointment->Date()->value.' '.$appointment->Time()->value;
}

function nextPropertyAppointmentsGenerator(): Generator
{
    return Eris\Generator\seq(Eris\Generator\tuple(
        Eris\Generator\choose(-5, 5),
        Eris\Generator\choose(0, 1439),
        Eris\Generator\elements('asignada', 'reprogramada', 'completada', 'cancelada'),
    ));
}

it('always picks an open appointment from today on, with none open before it', function () {
    $this->forAll(nextPropertyAppointmentsGenerator())->then(function (array $lines): void {
        $appointments = nextPropertyAppointments($lines);
        $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
        $repository->shouldReceive('findAllByStatusAndDateOrPatientId')->andReturn($appointments);

        $next = (new GetPatientNextAppointmentUseCase(nextPropertyAuthorization(), new AppointmentsService($repository)))
            ->execute(GetPatientNextAppointmentDTO::create(NEXT_PROPERTY_TODAY));

        $candidates = array_filter($appointments, nextPropertyIsOpenFromToday(...));

        if ($candidates === []) {
            expect($next)->toBeNull();

            return;
        }

        expect($next)->not->toBeNull()
            ->and(nextPropertyIsOpenFromToday($next))->toBeTrue();

        foreach ($candidates as $candidate) {
            expect(nextPropertyKey($candidate) >= nextPropertyKey($next))->toBeTrue();
        }
    });
});
