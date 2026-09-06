<?php

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories\CertificationRepositoryInterface;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models\CertificationModel;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;


final readonly class EloquentCertificationRepository implements CertificationRepositoryInterface {
    public function save(CertificationEntity $data): void
    {
        CertificationModel::updateOrCreate(
            ['id' => $data->Id()->value],
            [
                'name' => $data->Name()->value,
                'description' => $data->Description()->value,
                'status' => $data->Status()->value,
                'date' => $data->Date()->value,
                'image_url' => $data->ImageUrl()->value,
            ]
        );
    }

    public function destroy(CertificationId $id): void
    {
        CertificationModel::destroy($id->value);
    }

    public function findByIdAndName(?CertificationId $id, ?CertificationName $name): array
    {
        $query = CertificationModel::query();

        if ($id) {
            $query->where('id', $id->value);
        }

        if ($name) {
            $query->where('name', $name->value);
        }

        $certifications = $query->get();

        return $certifications->map(fn($cert) => $this->mapToDomain($cert))->toArray();
    }


    private function mapToDomain(object $model): CertificationEntity
    {
        return CertificationEntity::fromPrimitives(
            id: (string) $model->id,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            date: $model->date,
            imageUrl: $model->image_url,
            createdAt: $model->created_at->toDateTimeString(),
            updatedAt: $model->updated_at->toDateTimeString(),
        );
    }
}