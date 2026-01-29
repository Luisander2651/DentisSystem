<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Repositories;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

interface UserRepositoryInterface
{
    // Búsqueda por ID (retorna nulo si no existe)
    public function findById(UserId $id): ?UserEntity;

    /**
     * @return UserEntity[]
     */
    public function findByRoleAndStatus(?UserStatus $status, ?UserRoleId $role);

    // Guardado "Upsert" (Sirve para Crear y para Editar)
    public function save(UserEntity $user): void;

    // Borrado (Generalmente basta con el ID)
    public function delete(UserId $id): void;

    // Búsqueda por Criterios (Usamos DocBlock para tipado fuerte)
    /**
     * @return UserEntity[]
     */
    public function listActiveUsers(): array;

    /**
     * @return UserEntity[]
     */
    public function findByRole(UserRoleId $role): array;

    public function findByEmailExcludingId(UserEmail $email, ?UserId $excludeId): ?UserEntity;
}