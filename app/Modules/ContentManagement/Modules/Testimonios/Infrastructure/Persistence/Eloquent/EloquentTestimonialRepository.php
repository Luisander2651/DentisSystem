<?php

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;

final readonly class EloquentTestimonialRepository implements TestimonialRepositoryInterface
{
    public function save(TestimonialEntity $data): int
    {
        return (int) TestimonialModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'author' => $data->Author()->value,
                'description' => $data->Description()->value,
                'status' => $data->Status()->value,
            ]
        )->id;
    }

    public function destroy(TestimonialId $id): void
    {
        TestimonialModel::destroy($id->value);
    }

    public function findByIdAndNameAndStatus(?TestimonialId $id, ?TestimonialStatus $status, ?TestimonialDate $date): array
    {
        $query = TestimonialModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($status) {
            $query->where('status', $status->value);
        }

        if ($date) {
            $query->whereDate('date', $date->value);
        }

        $testimonials = $query->get();

        return $testimonials->map(fn ($testimonial) => $this->mapToDomain($testimonial))->toArray();
    }

    public function findPage(PageRequest $request): Page
    {
        $total = TestimonialModel::query()->count();

        $items = TestimonialModel::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($request->offsetFor($total))
            ->limit($request->size)
            ->get()
            ->map(fn (TestimonialModel $model): TestimonialEntity => $this->mapToDomain($model))
            ->all();

        return Page::create($items, $total, $request);
    }

    public function countByStatus(): array
    {
        $byStatus = TestimonialModel::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $visible = (int) ($byStatus[TestimonialStatus::visible()->value] ?? 0);
        $hidden = (int) ($byStatus[TestimonialStatus::hidden()->value] ?? 0);

        return ['total' => $visible + $hidden, 'visible' => $visible, 'hidden' => $hidden];
    }

    private function mapToDomain(object $model): TestimonialEntity
    {
        return TestimonialEntity::fromPrimitives(
            id: (string) $model->id,
            author: $model->author,
            description: $model->description,
            status: $model->status,
            createdAt: $model->created_at->toDateTimeString(),
            updatedAt: $model->updated_at->toDateTimeString(),
        );
    }
}
