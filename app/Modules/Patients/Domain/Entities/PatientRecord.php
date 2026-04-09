<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Entities;

use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\Entities\Patient;

final class PatientRecord
{
    private function __construct(
        private readonly Patient $patient,
        private readonly ?ContactInfo $contactInfo,
        private readonly ?Address $address,
        private readonly ?MedicalData $medicalData,
    ) {}

    public static function create(
        Patient $patient,
        ?ContactInfo $contactInfo,
        ?Address $address,
        ?MedicalData $medicalData,
    ): self {
        return new self(
            $patient,
            $contactInfo,
            $address,
            $medicalData,
        );
    }

    public function GetPatient(): Patient
    {
        return $this->patient;
    }

    public function GetContactInfo(): ?ContactInfo
    {
        return $this->contactInfo;
    }

    public function GetAddress(): ?Address
    {
        return $this->address;
    }

    public function GetMedicalData(): ?MedicalData
    {
        return $this->medicalData;
    }

}