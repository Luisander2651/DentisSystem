<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Service;

use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class ContactInfoService
{
    private readonly ContactInfoRepositoryInterface $contactInfoRepository;

    public function __construct(ContactInfoRepositoryInterface $contactInfoRepository)
    {
        $this->contactInfoRepository = $contactInfoRepository;
    }

    public function saveOrUpdateContactInfo(ContactInfo $contactInfo): void
    {
        $this->contactInfoRepository->save($contactInfo);
    }

    public function deleteContactInfo(PatientId $patientId): void
    {
        $this->contactInfoRepository->deleteByPatientId($patientId);
    }
}