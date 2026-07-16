<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;

interface TreatmentsRepositoryInterface
{
    public function save(TreatmentEntity $treatment): void;

    public function findById(TreatmentId $id): ?TreatmentEntity;

    public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array;

    public function delete(TreatmentId $id): void;

}