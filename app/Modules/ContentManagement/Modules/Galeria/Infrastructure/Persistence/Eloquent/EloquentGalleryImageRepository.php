<?php

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryStatus;

final readonly class EloquentGalleryImageRepository implements GalleryImageRepositoryInterface
{
    public function save(GalleryImageEntity $data): void
    {
        GalleryImageModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'url' => $data->Url()->value,
                'description' => $data->Description()->value,
                'status' => $data->Status()->value,
            ]
        );
    }

    public function destroy(GalleryImageId $id): void
    {
        GalleryImageModel::destroy($id->value);
    }

    public function findByIdAndUrlAndStatus(?GalleryImageId $id, ?GalleryImageUrl $url, ?GalleryStatus $status): array
    {
        $query = GalleryImageModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($url) {
            $query->where('url', $url->value);
        }

        $images = $query->get();

        return $images->map(fn ($image) => $this->mapToDomain($image))->toArray();
    }

    private function mapToDomain(object $model): GalleryImageEntity
    {
        return GalleryImageEntity::fromPrimitives(
            id: (string) $model->id,
            url: $model->url,
            description: (string) ($model->description ?? ''),
            status: (string) ($model->status ?? 'visible'),
            createdAt: $model->created_at->toDateTimeString(),
            updatedAt: $model->updated_at->toDateTimeString(),
        );
    }
}