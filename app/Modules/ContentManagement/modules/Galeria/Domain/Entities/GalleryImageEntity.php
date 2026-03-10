<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Galeria\Domain\Entities;

use App\Modules\ContentManagement\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Galeria\Domain\ValueObjects\ImageDescription;

use DateTimeImmutable;

final class GalleryImageEntity
{
    private function __construct(
        private readonly GalleryImageId $id,
        private readonly GalleryImageUrl $url,
        private ImageDescription $description,
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
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $url,
        string $description,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new GalleryImageId($id),
            new GalleryImageUrl($url),
            new ImageDescription($description),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function update(?string $newDescription, ?string $newUrl): void
    {
        if ($newDescription !== null) {
            $this->description = new ImageDescription($newDescription);
        }
        if ($newUrl !== null) {
            $this->url = new GalleryImageUrl($newUrl);
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

    public function CreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function UpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
