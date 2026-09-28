<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\ContactInfo;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Aplication\DTOs\ContactInfo\DeleteContactInfoDTO;
use App\Modules\Patients\Aplication\Exceptions\ContactInfo\ContactInfoAplicationExceptions;
use App\Modules\Patients\Domain\Service\ContactInfoService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class DeleteContactInfoByPatientIdUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private ContactInfoService $contactInfoService,
    ) {}

    public function execute(DeleteContactInfoDTO $dto): void
    {
        $this->authorization->assertCan('patients.clinical-data.manage');

        if ($dto->patientId === '') {
            throw ContactInfoAplicationExceptions::IdNotProvided();
        }

        $this->contactInfoService->deleteContactInfo(new PatientId($dto->patientId));
    }
}
