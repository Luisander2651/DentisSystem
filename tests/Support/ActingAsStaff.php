<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ActingAsStaff
{
    /**
     * Resolves a role by name, seeding the three canonical rows on first use.
     *
     * Unit 3 had to pin ids by hand here, because UserRoleId::fromDatabaseId() only maps
     * 1, 2 and 3 while Postgres sequences are not rolled back by RefreshDatabase, so
     * roles.id climbed past 3 as the suite ran and every path that rebuilt a UserEntity
     * started throwing. BR-17 moved that guarantee into RoleSeeder, a production
     * artefact, so the helper no longer has to compensate for it - it just seeds and
     * looks the row up.
     *
     * Names are matched case-insensitively, so a test may ask for 'administrador' or
     * 'Administrador' and get the same row.
     */
    protected function createRole(string $name): RoleModel
    {
        $this->seedRoles();

        return RoleModel::query()->findOrFail(UserRoleId::fromString($name)->toDatabaseId());
    }

    /**
     * Idempotent, and cheap after the first call: the seeder itself is an upsert.
     */
    protected function seedRoles(): void
    {
        (new RoleSeeder)->run();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function actingAsAdmin(array $overrides = []): UserModel
    {
        $user = $this->createUserWithRole('Administrador', $overrides);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    protected function actingAsInactiveAdmin(): UserModel
    {
        return $this->actingAsAdmin(['status' => 'inactive']);
    }

    /**
     * Any staff member who is not an administrator. Defaults to Asistente; 'Doctor' is
     * the other option. The pre-BR-16 default was 'recepcionista', a role that never
     * existed in the roles table.
     */
    protected function actingAsNonAdminUser(string $roleName = 'Asistente'): UserModel
    {
        $user = $this->createUserWithRole($roleName);
        $this->actingAs($user, 'sanctum');

        return $user;
    }
}
