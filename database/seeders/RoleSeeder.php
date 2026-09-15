<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * BR-17 - finding 8, Level B.
 *
 * UserRoleId translates a role to and from the `roles` primary key with a hardcoded
 * 1/2/3 mapping, but nothing in the repository produced those rows: `roles.id` is an
 * autoincrement assigned in insertion order, and there was no seeder at all. The domain
 * therefore assumed a database content no artefact guaranteed.
 *
 * Two ways that bit. With the ids in a different order, creating an assistant silently
 * produced a doctor. With an id past 3 - deleting and recreating one row is enough,
 * since Postgres sequences do not roll back - reading ANY user threw, taking the whole
 * listing down with it, because mapToDomain() runs over every result.
 *
 * This seeder turns the assumption into a versioned contract. It does NOT remove the
 * hardcoded mapping: replacing it with a real lookup against this table is Level C,
 * deferred to a dedicated iteration. See
 * aidlc-docs/construction/plans/unit-4-users-finding-8-analysis.md.
 *
 * Idempotent, so it is safe to re-run against an existing environment. The ids match
 * what the production database already holds, so no user's effective role changes.
 */
final class RoleSeeder extends Seeder
{
    /**
     * The role literal each hardcoded id must resolve to.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        $roles = [];

        foreach (UserRoleId::all() as $literal) {
            $roles[UserRoleId::fromString($literal)->toDatabaseId()] = $literal;
        }

        ksort($roles);

        return $roles;
    }

    public function run(): void
    {
        foreach (self::roles() as $id => $name) {
            $role = RoleModel::query()->find($id);

            if ($role === null) {
                // `id` is not fillable, so it has to be set outside mass assignment -
                // otherwise Eloquent drops it and the sequence hands out its own value.
                $role = new RoleModel(['name' => $name, 'description' => $name]);
                $role->id = $id;
                $role->save();

                continue;
            }

            $role->name = $name;
            $role->save();
        }

        $this->realignSequence();
    }

    /**
     * Postgres keeps the `roles_id_seq` counter independent of the rows, so after
     * inserting explicit ids the next autoincrement would collide with them. Realigning
     * keeps any future role creation away from the three pinned slots.
     */
    private function realignSequence(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('roles', 'id'), GREATEST((SELECT MAX(id) FROM roles), 1))"
        );
    }
}
