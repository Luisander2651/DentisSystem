<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\ContactInfo;

use App\Modules\Patients\Aplication\DTOs\ContactInfo\CreateContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\Repositories\ContactInfoRepositoryInterface;
use App\Modules\Patients\Domain\Service\ContactInfoService;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactEmail;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactInfoPatientId;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\EmergencyContact;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\PhoneNumber;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class SaveContactInfoUseCase
{
    public function __construct(
        private ContactInfoRepositoryInterface $contactInfoRepository,
        private ContactInfoService $contactInfoService,
    ) {}

    public function execute(CreateContactInfoDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw ContactInfoAplicationExceptions::IdNotProvided();
        }

        $existingContactInfo = $this->contactInfoRepository->findByPatientId(new PatientId($dto->patientId));

        if ($existingContactInfo !== null) {
            throw ContactInfoAplicationExceptions::AlreadyExists();
        }

        $contactInfo = ContactInfo::create(
            patientId: new ContactInfoPatientId($dto->patientId),
            phoneNumber: PhoneNumber::fromNullable($dto->phoneNumber),
            contactEmail: ContactEmail::fromNullable($dto->contactEmail),
            emergencyContact: EmergencyContact::fromNullable($dto->emergencyContact),
        );

        $this->contactInfoService->saveOrUpdateContactInfo($contactInfo);
    }
}
