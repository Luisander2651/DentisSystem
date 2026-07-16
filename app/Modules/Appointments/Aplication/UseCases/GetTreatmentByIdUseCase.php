<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetTreatmentByIdDTO;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;

final readonly class GetTreatmentByIdUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(GetTreatmentByIdDTO $dto): TreatmentEntity
    {
        $this->authorization->assertCan('treatments.view');

        $treatmentId = new TreatmentId($dto->id);
        $treatment = $this->treatmentsService->findById($treatmentId);

        if (! $treatment instanceof TreatmentEntity) {
            throw TreatmentException::notFound($treatmentId);
        }

        return $treatment;
    }
}