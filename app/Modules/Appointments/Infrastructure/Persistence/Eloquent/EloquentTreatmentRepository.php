<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent;

use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Repositories\TreatmentsRepositoryInterface;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;

final class EloquentTreatmentRepository implements TreatmentsRepositoryInterface
{
    public function save(TreatmentEntity $treatment): void
    {
        $data = [
            'id' => $treatment->Id()->value,
            'name' => $treatment->Name()->value,
            'description' => $treatment->Description()->value,
            'time' => $treatment->Time()->value,
        ];

        TreatmentModel::query()->updateOrCreate(['id' => $treatment->Id()->value], $data);
    }

    public function findById(TreatmentId $id): ?TreatmentEntity
    {
        $model = TreatmentModel::query()->find($id->value);

        return $model ? $this->mapToDomain($model) : null;
    }

    public function findAllByIdByName(?TreatmentId $id, ?TreatmentName $treatmentName): array
    {
        $query = TreatmentModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($treatmentName) {
            $query->where('name', 'like', '%' . $treatmentName->value . '%');
        }

        $results = $query->get();

        return $results->map(fn ($model) => $this->mapToDomain($model))->toArray();
    }

    public function delete(TreatmentId $id): void
    {
        TreatmentModel::destroy($id->value);
    }

    private function mapToDomain(object $model): TreatmentEntity
    {
        return TreatmentEntity::fromPrimitives(
            (string) $model->id,
            (string) $model->name,
            (string) $model->time,
            (string) $model->description,
            (string) $model->created_at,
            (string) $model->updated_at
        );
    }
}