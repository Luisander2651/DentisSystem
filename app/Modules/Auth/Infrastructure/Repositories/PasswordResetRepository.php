<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Repositories;

use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Users\Domain\ValueObjects\PasswordHash as UserPasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash as PatientPasswordHash;
use App\Modules\Auth\Infrastructure\Exceptions\Repositories\PasswordResetException;

final readonly class PasswordResetRepository
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PatientsRepositoryInterface $patientsRepository
    ) {}

    public function findUserByEmail(UserEmail $email): ?UserEntity
    {

        $entity = $this->userRepository->findByEmailExcludingId($email, null);
        if (!$entity) {
            throw PasswordResetException::UserNotFound();
        }
        return $entity;
    }

    public function findPatientByEmail(PatientEmail $email): ?Patient
    {
        $entity = $this->patientsRepository->findByEmailExcludingId($email, null);
        if (!$entity) {
            throw PasswordResetException::PatientNotFound();
        }
        return $entity;
    }

    public function updatePassword(UserEntity | Patient $entity, UserPasswordHash | PatientPasswordHash $passwordHash): void
    {
        if ($entity instanceof UserEntity && $passwordHash instanceof UserPasswordHash) {
            $entity->changePassword($passwordHash);
            $this->userRepository->save($entity);
        } elseif ($entity instanceof Patient && $passwordHash instanceof PatientPasswordHash) {
            $entity->changePassword($passwordHash);
            $this->patientsRepository->save($entity);
        } else {
            throw new \InvalidArgumentException('Invalid entity or password hash type.');
        }
    }
}