<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Illuminate\Support\Str;

function newStaff(string $role = 'Asistente'): UserEntity
{
    return UserEntity::create(
        name: UserName::create('Ana', 'Ruiz'),
        email: new UserEmail('ana@example.com'),
        password: PasswordHash::createFromPlainText('Sup3rSecret!'),
        roleId: new UserRoleId($role),
    );
}

it('creates a staff member who is always active', function () {
    // BR-3: there is no way to create an already-deactivated account, which is why BR-20
    // removed the SaveUserDTO::$status field that suggested otherwise.
    $user = newStaff();

    expect($user->status()->isActive())->toBeTrue()
        ->and(Str::isUuid($user->id()->value))->toBeTrue()
        ->and($user->name()->full())->toBe('Ana Ruiz');
});

it('updates only the fields it is given', function () {
    $user = newStaff('Asistente');

    $user->update(firstName: 'Luis', lastName: null, roleId: null, status: null);

    expect($user->name()->firstName)->toBe('Luis')
        ->and($user->name()->lastName)->toBe('Ruiz')
        ->and($user->role()->value)->toBe('Asistente')
        ->and($user->status()->isActive())->toBeTrue();
});

it('changes the role and the status through update', function () {
    $user = newStaff('Asistente');

    $user->update(firstName: null, lastName: null, roleId: 'Doctor', status: 'inactive');

    expect($user->role()->isDoctor())->toBeTrue()
        ->and($user->status()->isInactive())->toBeTrue();
});

it('accepts any capitalisation of the role on update', function () {
    $user = newStaff();

    $user->update(firstName: null, lastName: null, roleId: 'administrador', status: null);

    expect($user->role()->value)->toBe('Administrador');
});

it('replaces the credential and invalidates the previous one', function () {
    $user = newStaff();

    expect($user->isPasswordValid('Sup3rSecret!'))->toBeTrue();

    $user->changePassword(PasswordHash::createFromPlainText('An0therSecret!'));

    expect($user->isPasswordValid('An0therSecret!'))->toBeTrue()
        ->and($user->isPasswordValid('Sup3rSecret!'))->toBeFalse();
});

it('refreshes updatedAt on every mutation', function () {
    $user = newStaff();
    $before = $user->updatedAt();

    $user->update(firstName: null, lastName: null, roleId: null, status: 'inactive');

    expect($user->updatedAt()->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp());
});

it('no longer exposes the dead transition methods', function () {
    // BR-24: activate(), deactivate() and changeName() were never called by any use case;
    // update() covers all three. Two paths to the same transition invite divergence.
    expect(method_exists(UserEntity::class, 'activate'))->toBeFalse()
        ->and(method_exists(UserEntity::class, 'deactivate'))->toBeFalse()
        ->and(method_exists(UserEntity::class, 'changeName'))->toBeFalse();
});

it('keeps the email immutable', function () {
    // BR-4: neither update() nor the DTO accepts an email, which is what BR-23 turns into
    // an explicit 422 at the HTTP boundary instead of a silent discard.
    $reflection = new ReflectionMethod(UserEntity::class, 'update');

    expect(array_map(
        static fn (ReflectionParameter $p): string => $p->getName(),
        $reflection->getParameters(),
    ))->not->toContain('email');
});
