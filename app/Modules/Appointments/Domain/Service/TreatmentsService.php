<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Repositories\TreatmentsRepositoryInterface;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;

final readonly class TreatmentsService
{
    public function __construct(
        private TreatmentsRepositoryInterface $treatmentRepository
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

    /**
     * @return Page<TreatmentEntity>
     */
    public function findPage(PageRequest $request, ?string $search): Page
    {
        return $this->treatmentRepository->findPage($request, $search);
    }

    /**
     * @return array{total: int, with_duration: int, average_minutes: int|null}
     */
    public function stats(): array
    {
        return $this->treatmentRepository->stats();
    }
}
