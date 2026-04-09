<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent;

use App\Modules\Patients\Domain\Entities\PatientRecord;
use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Domain\Repositories\PatientRecordRepositoryInterface;

use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Override;

class PatientRecordRepository implements PatientRecordRepositoryInterface
{
    private readonly PatientsRepositoryInterface $patientsRepository;
    private readonly AddressesRepositoryInterface $addressesRepository;
    private readonly ContactInfoRepositoryInterface $contactInfoRepository;
    private readonly MedicalDataRepositoryInterface $medicalDataRepository;
    public function __construct(
        PatientsRepositoryInterface $patientsRepository,
        AddressesRepositoryInterface $addressesRepository,
        ContactInfoRepositoryInterface $contactInfoRepository,
        MedicalDataRepositoryInterface $medicalDataRepository
    ) {
        $this->patientsRepository = $patientsRepository;
        $this->addressesRepository = $addressesRepository;
        $this->contactInfoRepository = $contactInfoRepository;
        $this->medicalDataRepository = $medicalDataRepository;
    }

    public function GetByPatientId(PatientId $patientId): ?PatientRecord
    {
        $patient = $this->patientsRepository->findByPatientId($patientId);
        if (!$patient) {
            return null;
        }

        $address = $this->addressesRepository->findByPatientId($patientId);
        $contactInfo = $this->contactInfoRepository->findByPatientId($patientId);
        $medicalData = $this->medicalDataRepository->findByPatientId($patientId);

        return PatientRecord::create(
            patient: $patient,
            address: $address,
            contactInfo: $contactInfo,
            medicalData: $medicalData
        );
    }

}