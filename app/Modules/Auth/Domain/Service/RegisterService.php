<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Users\Domain\ValueObjects\UserEmail;

final class RegisterService
{
    public function __construct(
        private readonly PatientsRepositoryInterface $patientRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function registerPatient(
        Patient $patient,
        string $confirmPassword
    ): void {

        if (! $patient->PasswordHash()->verify($confirmPassword)) {
            throw AuthException::passwordsDoNotMatch();
        }

        if ($this->emailIsTaken($patient)) {
            throw AuthException::emailAlreadyInUse($patient->Email()->value);
        }

        $this->patientRepository->save($patient);
    }

    /**
     * BR-13: the email must be free across BOTH actor tables. LoginService resolves
     * staff before patients, so a patient registered with an email already owned by
     * a UserModel would never be able to authenticate with their own account.
     * The same exception is thrown for both cases so the response does not reveal
     * whether the colliding account is a patient or a staff member.
     */
    private function emailIsTaken(Patient $patient): bool
    {
        if ($this->patientRepository->findByEmailExcludingId($patient->Email(), null) !== null) {
            return true;
        }

        return $this->userRepository->findByEmailExcludingId(
            UserEmail::fromString($patient->Email()->value),
            null,
        ) !== null;
    }
}
