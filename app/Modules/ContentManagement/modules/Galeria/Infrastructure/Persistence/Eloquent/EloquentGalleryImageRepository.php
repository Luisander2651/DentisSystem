<?php

namespace App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;

final readonly class EloquentGalleryImageRepository implements GalleryImageRepositoryInterface
{
    public function save(GalleryImageEntity $data): void
    {
        GalleryImageModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'url' => $data->Url()->value,
                'description' => $data->Description()->value,
            ]
        );
    }

    public function destroy(GalleryImageId $id): void
    {
        GalleryImageModel::destroy($id->value);
    }

    public function findByIdAndUrl(?GalleryImageId $id, ?GalleryImageUrl $url): array
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
            createdAt: $model->created_at->toDateTimeString(),
            updatedAt: $model->updated_at->toDateTimeString(),
        );
    }
}