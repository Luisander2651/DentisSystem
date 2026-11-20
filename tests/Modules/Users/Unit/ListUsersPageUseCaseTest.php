<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Users\Aplication\DTOs\ListUsersPageDTO;
use App\Modules\Users\Aplication\UseCases\ListUsersPageUseCase;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 028 (CA5, CA14; P5): the staff list is read one page at a time with the counts by role of
 * the whole list, and only by someone allowed to see users: without the permission nothing is
 * read nor counted.
 */

final class UsersPageCallLog
{
    /** @var list<array{0: string, 1?: mixed}> */
    public array $calls = [];
}

function usersPageAuthorization(bool $allowed): AuthorizationServiceInterface
{
    return new class($allowed) implements AuthorizationServiceInterface
    {
        public function __construct(private bool $allowed) {}

        public function assertCan(string $permission): void
        {
            if (! $this->allowed || $permission !== 'users.view') {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            return $this->allowed && $permission === 'users.view';
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            throw AuthorizationException::unauthenticated();
        }
    };
}

function usersPageRepository(UsersPageCallLog $log): UserRepositoryInterface
{
    return new class($log) implements UserRepositoryInterface
    {
        public function __construct(private UsersPageCallLog $log) {}

        public function findPage(PageRequest $request, ?UserStatus $status, ?UserRoleId $role): Page
        {
            $this->log->calls[] = ['findPage', [$request->page, $request->size, $status?->value, $role?->value]];

            return Page::create([], 30, $request);
        }

        public function countByRole(): array
        {
            $this->log->calls[] = ['countByRole'];

            return ['total' => 30, 'administrador' => 2, 'asistente' => 20, 'doctor' => 8];
        }

        public function findById(UserId $id): ?UserEntity
        {
            $this->log->calls[] = ['findById'];

            return null;
        }

        public function findByRoleAndStatus(?UserStatus $status, ?UserRoleId $role): array
        {
            $this->log->calls[] = ['findByRoleAndStatus'];

            return [];
        }

        public function save(UserEntity $user): void
        {
            $this->log->calls[] = ['save'];
        }

        public function delete(UserId $id): void
        {
            $this->log->calls[] = ['delete'];
        }

        public function listActiveUsers(): array
        {
            $this->log->calls[] = ['listActiveUsers'];

            return [];
        }

        public function findByRole(UserRoleId $role): array
        {
            $this->log->calls[] = ['findByRole'];

            return [];
        }

        public function findByEmailExcludingId(UserEmail $email, ?UserId $excludeId): ?UserEntity
        {
            $this->log->calls[] = ['findByEmailExcludingId'];

            return null;
        }
    };
}

function usersPageUseCase(UsersPageCallLog $log, bool $allowed): ListUsersPageUseCase
{
    return new ListUsersPageUseCase(
        new UserService(usersPageRepository($log)),
        usersPageAuthorization($allowed),
    );
}

it('neither reads nor counts the staff without the permission', function () {
    $log = new UsersPageCallLog;

    expect(fn () => usersPageUseCase($log, false)->execute(ListUsersPageDTO::create(2, 12, 'active', 'Doctor')))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe([]);
});

it('gives the page asked for with the counts by role of the whole list', function () {
    $log = new UsersPageCallLog;

    $result = usersPageUseCase($log, true)->execute(ListUsersPageDTO::create(2, 12, 'inactive', 'doctor'));

    expect($result['page']->page)->toBe(2)
        ->and($result['page']->total)->toBe(30)
        ->and($result['counts'])->toBe(['total' => 30, 'administrador' => 2, 'asistente' => 20, 'doctor' => 8])
        ->and($log->calls)->toBe([
            ['findPage', [2, 12, 'inactive', 'Doctor']],
            ['countByRole'],
        ]);
});

it('asks for the first page of twelve with no filters when none is given', function () {
    $log = new UsersPageCallLog;

    usersPageUseCase($log, true)->execute(ListUsersPageDTO::create(null, null, '', ''));

    expect($log->calls[0])->toBe(['findPage', [1, 12, null, null]]);
});

it('never asks for more than fifty staff members at once', function () {
    $log = new UsersPageCallLog;

    usersPageUseCase($log, true)->execute(ListUsersPageDTO::create(1, 10000, null, null));

    expect($log->calls[0])->toBe(['findPage', [1, 50, null, null]]);
});
