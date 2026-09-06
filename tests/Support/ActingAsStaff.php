<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ActingAsStaff
{
    protected function createRole(string $name): RoleModel
    {
        return RoleModel::query()->firstOrCreate(
            ['name' => $name],
            ['description' => null],
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
