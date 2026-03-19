<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Service;

use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;

final class RegisterService
{
    public function __construct(
        private readonly PatientsRepositoryInterface $patientRepository,
    ) {}

    public function registerPatient(
        Patient $patient,
        PatientEmail $patientEmail,
    ): void {
       
        $emailInUse = $this->patientRepository->findByEmailExcludingId($patientEmail, null) !== null;

        if ($emailInUse) {
            throw AuthException::emailAlreadyInUse($patientEmail->value);
        }

        $this->patientRepository->save($patient);
    }
}
