<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserRoleException;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;

/**
 * BR-9 - characterisation of finding 8, DELIBERATELY LEFT UNFIXED in this unit.
 *
 * UserRoleId translates a role literal to and from the `roles` primary key with a
 * hand-written 1/2/3 mapping. Nothing in the repository guarantees those ids exist or
 * mean that: `roles.id` is an autoincrement assigned in insertion order, and before
 * BR-17 there was no seeder at all, so the domain simply assumed a database content no
 * artefact produced.
 *
 * These tests pin the mapping AS IT IS, failure mode included, so that the dedicated
 * iteration that replaces it has an explicit baseline to change. Level C of
 * aidlc-docs/construction/plans/unit-4-users-finding-8-analysis.md is the redesign:
 * resolve the role against the table instead of translating ids in the domain.
 *
 * RoleSeeder (BR-17) is the half that DID land here: it turns the 1/2/3 assumption into
 * a versioned contract, without touching the mapping itself.
 */
it('maps the three canonical roles to the ids RoleSeeder pins', function () {
    expect((new UserRoleId('Administrador'))->toDatabaseId())->toBe(1)
        ->and((new UserRoleId('Asistente'))->toDatabaseId())->toBe(2)
        ->and((new UserRoleId('Doctor'))->toDatabaseId())->toBe(3);
});

it('reads those same three ids back', function () {
    expect(UserRoleId::fromDatabaseId('1')->value)->toBe('Administrador')
        ->and(UserRoleId::fromDatabaseId('2')->value)->toBe('Asistente')
        ->and(UserRoleId::fromDatabaseId('3')->value)->toBe('Doctor');
});

it('throws for any role id outside 1, 2 and 3', function (string $id) {
    // This is the sharp edge. Postgres sequences do not roll back, so deleting and
    // recreating a roles row is enough to push an id past 3 - which is exactly what
    // happened during Unit 3 and forced the slot-pinning patch in
    // tests/Support/ActingAsStaff.php.
    expect(fn () => UserRoleId::fromDatabaseId($id))->toThrow(UserRoleException::class);
})->with(['0', '4', '5', '99', '-1']);

it('breaks the whole collection, not just the offending row', function () {
    // EloquentUserRepository::mapToDomain() runs over every result, so a single row
    // whose role_id falls outside the mapping takes the entire listing down with it.
    // This test states that consequence in the domain, without a database: the first
    // unmappable id aborts the map() over the batch.
    $roleIdsInTable = ['1', '2', '4', '3'];

    expect(fn () => array_map(
        static fn (string $id): UserRoleId => UserRoleId::fromDatabaseId($id),
        $roleIdsInTable,
    ))->toThrow(UserRoleException::class);
});
