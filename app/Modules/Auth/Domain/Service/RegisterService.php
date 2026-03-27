<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;

final class RegisterService
{

    public function __construct(
        private readonly PatientsRepositoryInterface $patientRepository,
    ) {}

    public function registerPatient(
        Patient $patient,
        string $confirmPassword
    ): void {

        if (!$patient->PasswordHash()->verify($confirmPassword)) {
            throw AuthException::passwordsDoNotMatch();
        }
       
        $emailInUse = $this->patientRepository->findByEmailExcludingId($patient->Email(), null);

        if ($emailInUse) {
            throw AuthException::emailAlreadyInUse($patient->Email()->value);
        }

        $this->patientRepository->save($patient);
    }
}
