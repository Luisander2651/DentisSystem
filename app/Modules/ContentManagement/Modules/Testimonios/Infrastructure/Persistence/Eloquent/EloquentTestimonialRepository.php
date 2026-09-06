<?php

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models\TestimonialModel;

final readonly class EloquentTestimonialRepository implements TestimonialRepositoryInterface
{
    public function save(TestimonialEntity $data): void
    {
        TestimonialModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'author' => $data->Author()->value,
                'description' => $data->Description()->value,
                'status' => $data->Status()->value,
            ]
        );
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