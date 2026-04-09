<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\ContactInfo;

use App\Modules\Patients\Aplication\DTOs\ContactInfo\DeleteContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Domain\Service\ContactInfoService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class DeleteContactInfoByPatientIdUseCase
{
    public function __construct(
        private ContactInfoService $contactInfoService,
    ) {}

    public function execute(DeleteContactInfoDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw ContactInfoAplicationExceptions::IdNotProvided();
        }

        $this->contactInfoService->deleteContactInfo(new PatientId($dto->patientId));
    }
}
