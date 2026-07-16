<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\CreateTreatmentDTO;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentDescription;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentTime;

final readonly class CreateTreatmentUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(CreateTreatmentDTO $dto): void
    {
        $this->authorization->assertCan('treatments.create');

        $treatment = TreatmentEntity::create(
            name: TreatmentName::fromString($dto->name),
            description: TreatmentDescription::fromString($dto->description),
            time: TreatmentTime::fromInt((int) $dto->time),
        );

        $this->treatmentsService->saveTreatment($treatment);
    }
}