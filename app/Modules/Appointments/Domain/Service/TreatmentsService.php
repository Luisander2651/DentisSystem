<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\EloquentTreatmentRepository;

final readonly class TreatmentsService
{
    public function __construct(
        private readonly EloquentTreatmentRepository $treatmentRepository
    ) {}

    public function saveTreatment(TreatmentEntity $treatment): void
    {
        $this->treatmentRepository->save($treatment);
    }

    public function findById(TreatmentId $id): ?TreatmentEntity
    {
        return $this->treatmentRepository->findById($id);
    }

    public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array
    {
        return $this->treatmentRepository->findAllByIdByName($id, $treatmentName);
    }

    public function deleteTreatment(TreatmentId $id): void
    {
        $this->treatmentRepository->delete($id);
    }
}