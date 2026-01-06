<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Eloquent;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?UserEntity
    {
        // Buscar un usuario por su ID
        $user = UserModel::find($id->value);
         
        if ($user === null) {
            return null;
        }
        
        return $user ? $this->mapToDomain($user) : null;
    }
    
    public function findByRole(UserRoleId $role): array
    {
        $users = UserModel::where('role_id', $role->value)->get();

        return $users ? $users->map(fn($user) => $this->mapToDomain($user))->toArray() : [];
    }

    // Búsqueda por Email excluyendo un ID específico o null (solo bsucar por email)
    public function findByEmailExcludingId(UserEmail $email, ?UserId $excludeId): ?UserEntity
    {
        $query = UserModel::where('email', $email->value);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value);
        }
        
        $user = $query->first();

        return $user ? $this->mapToDomain($user) : null;
    }

    public function save(UserEntity $user): void
    {
        // 1. Mapeo de datos para no repetir código
        $data = [
            'first_name' => $user->name()->firstName,
            'last_name'  => $user->name()->lastName,
            'email'      => $user->email()->value,
            'password'   => $user->password()->value,
            'role_id'    => $user->role()->toDatabaseId(),
            'status'     => $user->status()->value,
        ];

        // 2. Persistencia
        UserModel::updateOrCreate(
            ['id' => $user->id()->value],
            $data
        );
    }

    public function delete(UserId $id): void
    {
        UserModel::destroy($id->value);
    }

    public function listActiveUsers(): array
    {
        $users = UserModel::where('status', 'active')->get();

        return $users ? $users->map(fn($user) => $this->mapToDomain($user))->toArray() : [];
    }

    /**
     * Centralizamos la creación de la entidad aquí
     */
    private function mapToDomain(UserModel $user): UserEntity
    {
        return UserEntity::fromPrimitives(
            (string) $user->id,
            (string) $user->first_name,
            (string) $user->last_name,
            (string) $user->email,
            (string) $user->password,
            (string) $user->role_id,
            (string) $user->status,
            $user->created_at->toDateTimeString(),
            $user->updated_at->toDateTimeString()
        );
    }
}
