<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ActingAsStaff
{
    /**
     * UserRoleId::fromDatabaseId() only maps the database ids 1, 2 and 3, and
     * Postgres sequences are NOT rolled back by RefreshDatabase, so an
     * auto-incremented roles.id climbs past 3 as the suite runs and every code
     * path that rebuilds a UserEntity from the database starts throwing. Ids are
     * therefore pinned here: 1 = admin, 2 = asistent, 3 = doctor.
     */
    protected function createRole(string $name): RoleModel
    {
        $existing = RoleModel::query()->where('name', $name)->first();

        if ($existing !== null) {
            return $existing;
        }

        $preferred = match (strtolower($name)) {
            'administrador', 'admin' => 1,
            'doctor', 'odontologo' => 3,
            default => 2,
        };

        $taken = RoleModel::query()->pluck('id')->all();
        $id = $this->firstFreeRoleId($preferred, array_map('intval', $taken));

        // `id` is not fillable, so it has to be assigned outside mass assignment,
        // otherwise Eloquent drops it and Postgres hands out a sequence value again.
        $role = new RoleModel(['name' => $name, 'description' => null]);
        $role->id = $id;
        $role->save();

        return $role;
    }

    /**
     * Distinct role names can map to the same preferred slot - a test may create
     * both 'administrador' and 'ADMINISTRADOR' to exercise case-insensitive
     * matching - so the preferred id is only used when free.
     *
     * @param  list<int>  $taken
     */
    private function firstFreeRoleId(int $preferred, array $taken): int
    {
        foreach ([$preferred, 1, 2, 3] as $candidate) {
            if (! in_array($candidate, $taken, true)) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            'All three role slots (1, 2, 3) are in use. UserRoleId::fromDatabaseId '
            .'only maps those ids, so this test must reuse an existing role instead '
            .'of creating a fourth one.'
        );
    }

    protected function createUserWithRole(string $roleName, array $overrides = []): UserModel
    {
        $roleId = $overrides['role_id'] ?? $this->createRole($roleName)->id;

        return UserModel::create(array_merge([
            'id' => (string) Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ], $overrides, [
            'role_id' => $roleId,
        ]));
    }

    protected function actingAsAdmin(array $overrides = []): UserModel
    {
        $user = $this->createUserWithRole('administrador', $overrides);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    protected function actingAsInactiveAdmin(): UserModel
    {
        return $this->actingAsAdmin(['status' => 'inactive']);
    }

    protected function actingAsNonAdminUser(string $roleName = 'recepcionista'): UserModel
    {
        $user = $this->createUserWithRole($roleName);
        $this->actingAs($user, 'sanctum');

        return $user;
    }
}
