<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

/**
 * @param  array{0:string,1:string}  $namePair
 */
function makeUser(array $namePair, string $email, string $role = 'Asistente'): UserEntity
{
    return UserEntity::create(
        name: UserName::create($namePair[0], $namePair[1]),
        email: new UserEmail($email),
        password: PasswordHash::createFromPlainText('Sup3rSecret!'),
        roleId: new UserRoleId($role),
    );
}

// P12 - partial update preserves what was not sent. Every field passed as null must
// keep its previous value, which is the whole contract of PUT /v1/users/{id} (BR-5).
// A regression here would silently blank fields the admin never touched.

it('preserves every field left null by a partial update (property)', function () {
    $this->limitTo(20);

    $this->forAll($this->validUserNameGenerator(), $this->canonicalRoleGenerator())
        ->then(function (array $namePair, string $role) {
            $user = makeUser($namePair, 'staff@example.com', $role);

            $nameBefore = $user->name()->full();
            $roleBefore = $user->role()->value;
            $statusBefore = $user->status()->value;
            $emailBefore = $user->email()->value;

            $user->update(firstName: null, lastName: null, roleId: null, status: null);

            expect($user->name()->full())->toBe($nameBefore)
                ->and($user->role()->value)->toBe($roleBefore)
                ->and($user->status()->value)->toBe($statusBefore)
                ->and($user->email()->value)->toBe($emailBefore);
        });
});

it('changes only the field it was given (property)', function () {
    $this->limitTo(20);

    $this->forAll($this->canonicalRoleGenerator())->then(function (string $newRole) {
        $user = makeUser(['Ana', 'Ruiz'], 'staff@example.com', 'Asistente');

        $nameBefore = $user->name()->full();
        $statusBefore = $user->status()->value;

        $user->update(firstName: null, lastName: null, roleId: $newRole, status: null);

        expect($user->role()->value)->toBe((new UserRoleId($newRole))->value)
            ->and($user->name()->full())->toBe($nameBefore)
            ->and($user->status()->value)->toBe($statusBefore);
    });
});

it('never moves updatedAt backwards (property)', function () {
    $this->limitTo(20);

    $this->forAll($this->userStatusGenerator())->then(function (string $status) {
        $user = makeUser(['Ana', 'Ruiz'], 'staff@example.com');

        $before = $user->updatedAt();
        $user->update(firstName: null, lastName: null, roleId: null, status: $status);

        expect($user->updatedAt()->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp())
            ->and($user->updatedAt()->getTimestamp())->toBeGreaterThanOrEqual($user->createdAt()->getTimestamp());
    });
});

// P13 - round-trip across the persistence boundary. fromPrimitives() is the entry point
// of EVERY read: the repository maps each row through it, so a value that survives
// create() but not fromPrimitives() would break reading back what was just written -
// and, because mapToDomain() runs over the whole collection, would break the entire
// listing, not just that row.

it('reconstructs an equivalent entity from its own primitives (property)', function () {
    $this->limitTo(20);

    $this->forAll($this->validUserNameGenerator(), $this->canonicalRoleGenerator())
        ->then(function (array $namePair, string $role) {
            $original = makeUser($namePair, 'staff@example.com', $role);

            $rebuilt = UserEntity::fromPrimitives(
                $original->id()->value,
                $original->name()->firstName,
                $original->name()->lastName,
                $original->email()->value,
                $original->password()->value,
                (string) $original->role()->toDatabaseId(),
                $original->status()->value,
                $original->createdAt()->format('Y-m-d H:i:s'),
                $original->updatedAt()->format('Y-m-d H:i:s'),
            );

            expect($rebuilt->id()->value)->toBe($original->id()->value)
                ->and($rebuilt->name()->full())->toBe($original->name()->full())
                ->and($rebuilt->email()->value)->toBe($original->email()->value)
                ->and($rebuilt->role()->value)->toBe($original->role()->value)
                ->and($rebuilt->status()->value)->toBe($original->status()->value)
                ->and($rebuilt->password()->verify('Sup3rSecret!'))->toBeTrue();
        });
});

it('always starts a newly created user as active (property)', function () {
    $this->limitTo(20);

    $this->forAll($this->validUserNameGenerator(), $this->canonicalRoleGenerator())
        ->then(function (array $namePair, string $role) {
            // BR-3: there is no way to create an already-deactivated staff member, and
            // BR-20 removes the unused SaveUserDTO::$status that suggested otherwise.
            expect(makeUser($namePair, 'staff@example.com', $role)->status()->isActive())->toBeTrue();
        });
});
