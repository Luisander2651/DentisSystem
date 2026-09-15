<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserRoleException;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsesEris::class, UserGenerators::class);

// P8 and P9 describe the vocabulary AFTER BR-16. Before it, the VO spoke
// `admin`/`asistent`/`doctor` while the roles table stored `Administrador`/`Asistente`/
// `Doctor`, and the mismatch was papered over by a hand-written translation table in
// resources/js/pages/usuarios/edit-user.js. These properties fail until Step 7 lands,
// which is the point: they pin the target vocabulary, not the old one.

// P9 - case-insensitive construction with a stable canonical form. A client may send
// any capitalisation; what the VO exposes is always the literal the roles table holds,
// so `->value` can be compared against the database without normalising at each use.

it('collapses every capitalisation to the same canonical literal (property)', function () {
    $this->forAll($this->anyCaseRoleGenerator())->then(function (string $literal) {
        $role = new UserRoleId($literal);

        expect($role->value)->toBeIn($this->canonicalRoles())
            ->and(mb_strtolower($role->value))->toBe(mb_strtolower($literal));
    });
});

it('is idempotent over its own canonical value (property)', function () {
    $this->forAll($this->anyCaseRoleGenerator())->then(function (string $literal) {
        $once = new UserRoleId($literal);
        $twice = new UserRoleId($once->value);

        expect($twice->value)->toBe($once->value);
    });
});

it('rejects every literal outside the catalogue, the old vocabulary included (property)', function () {
    $this->forAll($this->invalidRoleGenerator())->then(function (string $literal) {
        expect(fn () => new UserRoleId($literal))->toThrow(UserRoleException::class);
    });
});

// P8 - round-trip across the persistence boundary. The 1/2/3 mapping stays hardcoded
// in this unit (BR-9, finding 8 at Level B), so what is asserted here is only that the
// two directions agree with each other. Their agreement with the actual roles table is
// what RoleSeeder pins (BR-17), and replacing the mapping altogether is deferred to a
// dedicated iteration.

it('round-trips between the role literal and its database id (property)', function () {
    $this->forAll($this->canonicalRoleGenerator())->then(function (string $literal) {
        $role = new UserRoleId($literal);
        $reconstructed = UserRoleId::fromDatabaseId((string) $role->toDatabaseId());

        expect($reconstructed->value)->toBe($role->value);
    });
});

it('maps every canonical role to a distinct database id (property)', function () {
    $ids = array_map(
        fn (string $literal): int => (new UserRoleId($literal))->toDatabaseId(),
        $this->canonicalRoles(),
    );

    expect($ids)->toBe(array_unique($ids))
        ->and($ids)->toEqualCanonicalizing([1, 2, 3]);
});
