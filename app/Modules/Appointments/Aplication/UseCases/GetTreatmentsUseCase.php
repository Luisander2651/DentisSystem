<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetTreatmentsDTO;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;

final readonly class GetTreatmentsUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return TreatmentEntity[]
     */
    public function execute(GetTreatmentsDTO $dto): array
    {
        $this->authorization->assertCan('treatments.view');

        $id = $dto->id !== null ? new TreatmentId($dto->id) : null;
        $name = $dto->name !== null ? TreatmentName::fromString($dto->name) : null;

        return $this->treatmentsService->findAllByIdByName($id, $name);
    }
}