<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

/**
 * Spec 028 (CA5, CA7): the staff list is read one page at a time, newest first, and counted by
 * role over the whole list.
 */
function usersPageService(): UserService
{
    return app(UserService::class);
}

/**
 * @param  list<UserEntity>  $users
 * @return list<string>
 */
function usersPageIds(array $users): array
{
    return array_map(fn (UserEntity $user): string => $user->id()->value, $users);
}

function usersPageAge(UserModel $user, Carbon $createdAt): void
{
    $user->forceFill(['created_at' => $createdAt])->save();
}

it('reads the third page of thirty staff members with the six left, newest first', function () {
    for ($i = 1; $i <= 30; $i++) {
        usersPageAge(
            $this->createUserWithRole('Asistente', ['first_name' => "Nombre{$i}"]),
            Carbon::parse('2026-01-01 08:00:00')->addMinutes($i),
        );
    }

    $first = usersPageService()->findPage(PageRequest::create(1, 12), null, null);
    $third = usersPageService()->findPage(PageRequest::create(3, 12), null, null);

    expect($first->items[0]->name()->firstName)->toBe('Nombre30')
        ->and($third->items)->toHaveCount(6)
        ->and($third->total)->toBe(30)
        ->and($third->lastPage)->toBe(3)
        ->and($third->items[5]->name()->firstName)->toBe('Nombre1');
});

it('gives the last page when the page asked for goes beyond it', function () {
    for ($i = 1; $i <= 13; $i++) {
        $this->createUserWithRole('Doctor');
    }

    $page = usersPageService()->findPage(PageRequest::create(99, 12), null, null);

    expect($page->page)->toBe(2)->and($page->items)->toHaveCount(1);
});

it('filters the page by status and by role, apart and together', function () {
    $this->createUserWithRole('Administrador');
    $this->createUserWithRole('Asistente');
    $this->createUserWithRole('Doctor');
    $this->createUserWithRole('Doctor', ['status' => 'inactive']);

    $byStatus = usersPageService()->findPage(PageRequest::create(1, 12), UserStatus::inactive(), null);
    $byRole = usersPageService()->findPage(PageRequest::create(1, 12), null, UserRoleId::doctor());
    $both = usersPageService()->findPage(PageRequest::create(1, 12), UserStatus::active(), UserRoleId::doctor());

    expect($byStatus->total)->toBe(1)
        ->and($byRole->total)->toBe(2)
        ->and($both->total)->toBe(1)
        ->and($both->items[0]->status()->value)->toBe('active');
});

it('gives every staff member exactly once across the pages when they share the creation time', function () {
    $sameTime = Carbon::parse('2026-01-01 08:00:00');

    for ($i = 1; $i <= 25; $i++) {
        usersPageAge($this->createUserWithRole('Asistente'), $sameTime);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...usersPageIds(usersPageService()->findPage(PageRequest::create($number, 12), null, null)->items)];
    }

    expect($seen)->toBe(UserModel::query()->pluck('id')->sortDesc()->values()->all());
});

it('counts the whole staff by role, inactive members included', function () {
    $this->createUserWithRole('Administrador');
    $this->createUserWithRole('Asistente');
    $this->createUserWithRole('Asistente', ['status' => 'inactive']);
    $this->createUserWithRole('Doctor');
    $this->createUserWithRole('Doctor');
    $this->createUserWithRole('Doctor', ['status' => 'inactive']);

    expect(usersPageService()->countByRole())
        ->toBe(['total' => 6, 'administrador' => 1, 'asistente' => 2, 'doctor' => 3]);
});

it('counts zero for every role of an empty staff', function () {
    expect(usersPageService()->countByRole())
        ->toBe(['total' => 0, 'administrador' => 0, 'asistente' => 0, 'doctor' => 0]);
});

it('keeps the doctors of the agenda as a whole list', function () {
    for ($i = 1; $i <= 15; $i++) {
        $this->createUserWithRole('Doctor');
    }

    expect(usersPageService()->findByRoleAndStatus(UserStatus::active(), UserRoleId::doctor()))->toHaveCount(15);
});
