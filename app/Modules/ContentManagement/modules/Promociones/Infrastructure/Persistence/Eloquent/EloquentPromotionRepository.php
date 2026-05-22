<?php

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;

final readonly class EloquentPromotionRepository implements PromotionRepositoryInterface
{
    public function save(PromotionEntity $data): void
    {
        PromotionModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'name' => $data->Name()->value,
                'description' => $data->Description()->value,
                'status' => $data->Status()->value,
                'discount_percentage' => $data->DiscountPercentage()->value,
                'start_date' => $data->StartDate()->value,
                'end_date' => $data->EndDate()->value,
            ]
        );
    }

    public function destroy(PromotionId $id): void
    {
        PromotionModel::destroy($id->value);
    }

    public function findByIdAndName(?PromotionId $id, ?PromotionName $name, ?PromotionStatus $status): array
    {
        $query = PromotionModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($name) {
            $query->where('name', $name->value);
        }

        if ($status) {
            $query->where('status', $status->value);
        }

        $promotions = $query->get();

        return $promotions->map(fn ($promotion) => $this->mapToDomain($promotion))->toArray();
    }

    private function mapToDomain(object $model): PromotionEntity
    {
        return PromotionEntity::fromPrimitives(
            id: (string) $model->id,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            discountPercentage: (float) $model->discount_percentage,
            startDate: $model->start_date,
            endDate: $model->end_date,
            createdAt: $model->created_at->toDateTimeString(),
            updatedAt: $model->updated_at->toDateTimeString(),
        );
    }
}