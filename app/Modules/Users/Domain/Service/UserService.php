<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Service;

use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Exceptions\UserException;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

class UserService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function saveUser(UserEntity $user): void
    {
        // Crear entidad de usuario
        // Buscamos si el email ya existe en OTRO usuario distinto a este
        $emailExists = $this->userRepository->findByEmailExcludingId($user->email(), $user->id()) !== null;

        // Lógica de seguridad para Emails Duplicados
        if ($emailExists) {
            throw UserException::shouldBeUniqueEmail($user->email());
        }

        // Ejecutar funcion save del repositorio
        $this->userRepository->save($user);
    }

    public function updateUser(UserEntity $user): void
    {
        $userExist = $this->userRepository->findById($user->id());

        if (!$userExist) {
            throw UserException::notFound($user->id());
        }
        
        $this->userRepository->save($user);
    }

    /**
     * @return UserEntity[]
     */
    public function findByRoleAndStatus(?UserStatus $status, ?UserRoleId $role): array
    {
        return $this->userRepository->findByRoleAndStatus($status, $role);
    }

    public function findById(UserId $id): UserEntity
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw UserException::notFound($id);
        }

        return $user;
    }

    public function deleteById(UserId $id): void
    {
        // ¿Existe el usuario antes de intentar borrarlo? 
        // Esto permite lanzar una excepción de dominio clara si el ID es basura.
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw UserException::notFound($id);
        }

        $this->userRepository->delete($id);
    }
}
