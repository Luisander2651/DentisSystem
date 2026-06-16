<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Entities;

use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\ImageDescription;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryStatus;

use DateTimeImmutable;

final class GalleryImageEntity
{
    private function __construct(
        private readonly GalleryImageId $id,
        private  GalleryImageUrl $url,
        private ImageDescription $description,
        private GalleryStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        GalleryImageUrl $url,
        ImageDescription $description,
    ): self {
        return new self(
            GalleryImageId::fromInt(0), // Se ignorará en BD (autoincrement)
            $url,
            $description,
            GalleryStatus::visible(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $url,
        string $description,
        string $status,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new GalleryImageId($id),
            new GalleryImageUrl($url),
            new ImageDescription($description),
            GalleryStatus::fromString($status),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function update(?string $newDescription, ?string $newUrl, ?string $newStatus = null): void
    {
        if ($newDescription !== null) {
            $this->description = new ImageDescription($newDescription);
        }
        if ($newUrl !== null) {
            $this->url = new GalleryImageUrl($newUrl);
        }
        if ($newStatus !== null && $newStatus !== '') {
            $this->status = GalleryStatus::fromString($newStatus);
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): GalleryImageId
    {
        return $this->id;
    }

    public function Url(): GalleryImageUrl
    {
        return $this->url;
    }

    public function Description(): ImageDescription
    {
        return $this->description;
    }

    public function Status(): GalleryStatus
    {
        return $this->status;
    }

    public function CreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function UpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
