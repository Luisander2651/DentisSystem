<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\Support\DoctorScope;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 020 (D11, 018/R5): the narrowing of the doctor, shared by the agenda and the dashboard.
 * Who may see the whole agenda gets none, the doctor only ever their own id, anyone else is
 * refused.
 */

const DOCTOR_SCOPE_ACTOR_ID = '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f';

/**
 * @param  list<string>  $granted
 */
function doctorScopeAuthorization(array $granted): AuthorizationServiceInterface
{
    return new class($granted) implements AuthorizationServiceInterface
    {
        /**
         * @param  list<string>  $granted
         */
        public function __construct(private array $granted) {}

        public function assertCan(string $permission): void
        {
            if (! $this->can($permission)) {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            return in_array($permission, $this->granted, true);
        }

        public function currentActorId(): string
        {
            return DOCTOR_SCOPE_ACTOR_ID;
        }
    };
}

it('does not narrow who may see the whole agenda', function (array $granted) {
    expect(DoctorScope::forActor(doctorScopeAuthorization($granted)))->toBeNull();
})->with([
    'administrador' => [['appointments.view', 'appointments.view-own']],
    'asistente' => [['appointments.view']],
])->skip('020: pendiente de T004');

it('narrows the doctor to their own id', function () {
    $scope = DoctorScope::forActor(doctorScopeAuthorization(['appointments.view-own']));

    expect($scope)->toBeInstanceOf(AppointmentUserId::class)
        ->and($scope->value)->toBe(DOCTOR_SCOPE_ACTOR_ID);
})->skip('020: pendiente de T004');

it('refuses anyone who may not see appointments (abuse)', function (array $granted) {
    expect(fn () => DoctorScope::forActor(doctorScopeAuthorization($granted)))
        ->toThrow(AuthorizationException::class);
})->with([
    'sin permisos' => [[]],
    'otros permisos' => [['patients.view', 'reports.view']],
])->skip('020: pendiente de T004');
