<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;

interface TreatmentsRepositoryInterface
{
    public function save(TreatmentEntity $treatment): void;

    public function findById(TreatmentId $id): ?TreatmentEntity;

    public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array;

    public function delete(TreatmentId $id): void;

    /**
     * Spec 028 (CA4, CA7, D16): one page of the treatments by name, with a literal search over the
     * name, the description or the minutes that does not tell accents apart.
     *
     * @return Page<TreatmentEntity>
     */
    public function findPage(PageRequest $request, ?string $search): Page;

    /**
     * Spec 028 (CA5): the stats of the whole catalogue; the average is in whole minutes, or null
     * when no treatment has a duration.
     *
     * @return array{total: int, with_duration: int, average_minutes: int|null}
     */
    public function stats(): array;
}
