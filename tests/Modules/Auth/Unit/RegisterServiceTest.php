<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Auth\Domain\Service\RegisterService;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\ValueObjects\PasswordHash as UserPasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;

/**
 * BR-7 is now also enforced by RegisterRequest's `same:password` rule, so the HTTP
 * layer answers 422 before reaching the service. The domain check is kept as
 * defence in depth and pinned here directly.
 */
function makePatient(string $password = 'Sup3rSecret!'): Patient
{
    return Patient::create(
        name: PatientName::create('John', 'Doe'),
        email: new PatientEmail('john.doe@example.com'),
        passwordHash: PasswordHash::createFromPlainText($password),
    );
}

it('rejects a confirmation that does not match the password (BR-7)', function () {
    $service = new RegisterService(
        Mockery::mock(PatientsRepositoryInterface::class),
        Mockery::mock(UserRepositoryInterface::class),
    );

    expect(fn () => $service->registerPatient(makePatient(), 'a-different-password'))
        ->toThrow(AuthException::class, 'The provided password and confirmation do not match.');
});

it('rejects an email already held by a patient (BR-13)', function () {
    $patients = Mockery::mock(PatientsRepositoryInterface::class);
    $patients->shouldReceive('findByEmailExcludingId')->once()->andReturn(makePatient());
    $patients->shouldNotReceive('save');

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldNotReceive('findByEmailExcludingId');

    $service = new RegisterService($patients, $users);

    expect(fn () => $service->registerPatient(makePatient(), 'Sup3rSecret!'))
        ->toThrow(AuthException::class);
});

it('rejects an email already held by a staff member (BR-13)', function () {
    $patients = Mockery::mock(PatientsRepositoryInterface::class);
    $patients->shouldReceive('findByEmailExcludingId')->once()->andReturnNull();
    $patients->shouldNotReceive('save');

    // UserEntity is final, so a real instance is built instead of a mock.
    $existingStaff = UserEntity::create(
        name: UserName::create('Staff', 'Member'),
        email: new UserEmail('john.doe@example.com'),
        password: UserPasswordHash::createFromPlainText('Sup3rSecret!'),
        roleId: UserRoleId::admin(),
    );

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByEmailExcludingId')->once()->andReturn($existingStaff);

    $service = new RegisterService($patients, $users);

    expect(fn () => $service->registerPatient(makePatient(), 'Sup3rSecret!'))
        ->toThrow(AuthException::class);
});

it('persists the patient when the email is free in both tables', function () {
    $patients = Mockery::mock(PatientsRepositoryInterface::class);
    $patients->shouldReceive('findByEmailExcludingId')->once()->andReturnNull();
    $patients->shouldReceive('save')->once();

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByEmailExcludingId')->once()->andReturnNull();

    $service = new RegisterService($patients, $users);
    $service->registerPatient(makePatient(), 'Sup3rSecret!');

    expect(true)->toBeTrue();
});
