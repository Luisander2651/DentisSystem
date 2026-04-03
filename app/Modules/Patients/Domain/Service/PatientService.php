<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Service;

use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Exceptions\PatientException;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientRole;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

class PatientService
{
    private PatientsRepositoryInterface $patientRepository;

    public function __construct(PatientsRepositoryInterface $patientRepository)
    {
        $this->patientRepository = $patientRepository;
    }

    public function savePatient(Patient $patient): void
    {
        // Verificar si el email ya existe en otro paciente distinto a este
        $emailExists = $this->patientRepository->findByEmailExcludingId($patient->Email(), $patient->Id()) !== null;
        
        if ($emailExists) {
            throw PatientException::shouldBeUniqueEmail($patient->Email());
        }

        $this->patientRepository->save($patient);
    }

    public function updatePatient(Patient $patient): void
    {
        $patientExists = $this->patientRepository->findByPatientId($patient->Id());

        if (!$patientExists) {
            throw PatientException::notFound($patient->Id());
        }
      $this->patientRepository->save($patient);
    }

    /**
     * @return Patient[]
     */
    public function findByRoleAndStatus(?PatientStatus $status): array
    {
        return $this->patientRepository->findByRoleAndStatus($status);
    }

    public function findById(PatientId $id): Patient
    {
        $patient = $this->patientRepository->findByPatientId($id);

        if (!$patient) {
            throw PatientException::notFound($id);
        }

        return $patient;
    }

    public function deleteById(PatientId $id): void
    {
        $patient = $this->patientRepository->findByPatientId($id);

        if (!$patient) {
            throw PatientException::notFound($id);
        }

        $this->patientRepository->delete($id);
    }
}
