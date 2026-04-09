<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\ContactInfo;

use App\Modules\Patients\Aplication\DTOs\ContactInfo\UpdateContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\Service\ContactInfoService;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactEmail;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\EmergencyContact;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\PhoneNumber;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class UpdateContactInfoUseCase
{
    public function __construct(
        private ContactInfoRepositoryInterface $contactInfoRepository,
        private ContactInfoService $contactInfoService,
    ) {}

    public function execute(string $patientId, UpdateContactInfoDTO $dto): void
    {
        if ($patientId === '') {
            throw ContactInfoAplicationExceptions::IdNotProvided();
        }

        if (!$dto->hasValue()) {
            throw ContactInfoAplicationExceptions::NoInfoProvided();
        }

        $contactInfo = $this->contactInfoRepository->findByPatientId(new PatientId($patientId));

        if ($contactInfo === null) {
            throw ContactInfoAplicationExceptions::NotFound();
        }

        $contactInfo->update(
            phoneNumber: $dto->phoneNumber !== null ? PhoneNumber::fromNullable($dto->phoneNumber) : null,
            contactEmail: $dto->contactEmail !== null ? ContactEmail::fromNullable($dto->contactEmail) : null,
            emergencyContact: $dto->emergencyContact !== null ? EmergencyContact::fromNullable($dto->emergencyContact) : null,
        );

        $this->contactInfoService->saveOrUpdateContactInfo($contactInfo);
    }
}
