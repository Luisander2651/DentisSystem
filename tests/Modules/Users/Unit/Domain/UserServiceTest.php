<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

/**
 * An in-memory repository rather than a mock: these tests are about the service's own
 * decisions, and a fake that actually stores rows lets each test assert the outcome
 * instead of the call sequence.
 */
function inMemoryUserRepository(): UserRepositoryInterface
{
    return new class implements UserRepositoryInterface
    {
        /** @var array<string, UserEntity> */
        public array $rows = [];

        public int $saveCalls = 0;

        public int $findByIdCalls = 0;

        public function findById(UserId $id): ?UserEntity
        {
            $this->findByIdCalls++;

            return $this->rows[$id->value] ?? null;
        }

        public function findByRoleAndStatus(?UserStatus $status, ?UserRoleId $role): array
        {
            return array_values(array_filter($this->rows, function (UserEntity $user) use ($status, $role): bool {
                if ($status !== null && $user->status()->value !== $status->value) {
                    return false;
                }

                return $role === null || $user->role()->value === $role->value;
            }));
        }

        public function save(UserEntity $user): void
        {
            $this->saveCalls++;
            $this->rows[$user->id()->value] = $user;
        }

        public function delete(UserId $id): void
        {
            unset($this->rows[$id->value]);
        }

        public function listActiveUsers(): array
        {
            return array_values(array_filter(
                $this->rows,
                static fn (UserEntity $user): bool => $user->status()->isActive(),
            ));
        }

        public function findByRole(UserRoleId $role): array
        {
            return $this->findByRoleAndStatus(null, $role);
        }

        public function findByEmailExcludingId(UserEmail $email, ?UserId $excludeId): ?UserEntity
        {
            foreach ($this->rows as $user) {
                if ($user->email()->value !== $email->value) {
                    continue;
                }

                if ($excludeId !== null && $user->id()->value === $excludeId->value) {
                    continue;
                }

                return $user;
            }

            return null;
        }
    };
}

function staffWithEmail(string $email, string $role = 'Asistente'): UserEntity
{
    return UserEntity::create(
        name: UserName::create('Ana', 'Ruiz'),
        email: new UserEmail($email),
        password: PasswordHash::createFromPlainText('Sup3rSecret!'),
        roleId: new UserRoleId($role),
    );
}

it('saves a staff member whose email is free', function () {
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);

    $service->saveUser(staffWithEmail('ana@example.com'));

    expect($repository->rows)->toHaveCount(1);
});

it('refuses an email that already belongs to another staff member', function () {
    // BR-2. Note the limit this pins: uniqueness is checked against `users` only, never
    // against the patients table, so the same address can exist as both.
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);

    $service->saveUser(staffWithEmail('ana@example.com'));

    expect(fn () => $service->saveUser(staffWithEmail('ana@example.com')))
        ->toThrow(UserException::class, 'The email ana@example.com is already in use.');
});

it('lets a staff member keep their own email when saved again', function () {
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);
    $user = staffWithEmail('ana@example.com');

    $service->saveUser($user);
    $service->saveUser($user);

    expect($repository->rows)->toHaveCount(1);
});

it('updates without re-reading the row for existence', function () {
    // BR-26: UpdateUserUseCase already proved existence via findById(), so a second query
    // could only confirm what the first one established.
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);
    $user = staffWithEmail('ana@example.com');
    $repository->save($user);

    $callsBefore = $repository->findByIdCalls;
    $service->updateUser($user);

    expect($repository->findByIdCalls)->toBe($callsBefore);
});

it('reports a missing user when looking one up', function () {
    $service = new UserService(inMemoryUserRepository());
    $id = UserId::random();

    expect(fn () => $service->findById($id))
        ->toThrow(UserException::class, "User with ID {$id->value} not found.");
});

it('refuses to delete a user that does not exist', function () {
    $service = new UserService(inMemoryUserRepository());

    expect(fn () => $service->deleteById(UserId::random()))->toThrow(UserException::class);
});

it('deletes an existing user', function () {
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);
    $user = staffWithEmail('ana@example.com');
    $repository->save($user);

    $service->deleteById($user->id());

    expect($repository->rows)->toBeEmpty();
});

it('filters by status and role independently and together', function () {
    $repository = inMemoryUserRepository();
    $service = new UserService($repository);

    $repository->save(staffWithEmail('a@example.com', 'Asistente'));
    $doctor = staffWithEmail('b@example.com', 'Doctor');
    $doctor->update(firstName: null, lastName: null, roleId: null, status: 'inactive');
    $repository->save($doctor);

    expect($service->findByRoleAndStatus(null, null))->toHaveCount(2)
        ->and($service->findByRoleAndStatus(UserStatus::active(), null))->toHaveCount(1)
        ->and($service->findByRoleAndStatus(null, UserRoleId::doctor()))->toHaveCount(1)
        ->and($service->findByRoleAndStatus(UserStatus::active(), UserRoleId::doctor()))->toHaveCount(0);
});
