<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\UpdateTreatmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\TreatmentAplicationExceptions;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;

final readonly class UpdateTreatmentUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(UpdateTreatmentDTO $dto): void
    {
        $this->authorization->assertCan('treatments.update');

        if (! $dto->hasValue()) {
            throw TreatmentAplicationExceptions::noInfoProvided();
        }

        $treatmentId = new TreatmentId($dto->id);
        $treatment = $this->treatmentsService->findById($treatmentId);

        if (! $treatment instanceof TreatmentEntity) {
            throw TreatmentException::notFound($treatmentId);
        }

        $treatment->update(
            $dto->name,
            $dto->description,
            $dto->time,
        );

        $this->treatmentsService->saveTreatment($treatment);
    }
}