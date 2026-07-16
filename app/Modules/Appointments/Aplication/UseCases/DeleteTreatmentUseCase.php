<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\DeleteTreatmentDTO;
use App\Modules\Appointments\Domain\Exceptions\TreatmentException;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;

final readonly class DeleteTreatmentUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(DeleteTreatmentDTO $dto): void
    {
        $this->authorization->assertCan('treatments.delete');

        $treatmentId = new TreatmentId($dto->id);
        $treatment = $this->treatmentsService->findById($treatmentId);

        if ($treatment === null) {
            throw TreatmentException::notFound($treatmentId);
        }

        $this->treatmentsService->deleteTreatment($treatmentId);
    }
}