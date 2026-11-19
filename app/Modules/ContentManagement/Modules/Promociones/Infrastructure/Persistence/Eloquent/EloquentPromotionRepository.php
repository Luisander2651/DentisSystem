<?php

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;

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

    public function findPage(PageRequest $request): Page
    {
        $total = PromotionModel::query()->count();

        $items = PromotionModel::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($request->offsetFor($total))
            ->limit($request->size)
            ->get()
            ->map(fn (PromotionModel $model): PromotionEntity => $this->mapToDomain($model))
            ->all();

        return Page::create($items, $total, $request);
    }

    public function countByStatus(): array
    {
        $byStatus = PromotionModel::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $visible = (int) ($byStatus[PromotionStatus::visible()->value] ?? 0);
        $hidden = (int) ($byStatus[PromotionStatus::hidden()->value] ?? 0);

        return ['total' => $visible + $hidden, 'visible' => $visible, 'hidden' => $hidden];
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
