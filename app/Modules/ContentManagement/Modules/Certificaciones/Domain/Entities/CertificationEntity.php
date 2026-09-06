<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationDescription;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationStatus;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationDate;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\ImageUrl;

use DateTimeImmutable;

final class CertificationEntity
{
    private function __construct(
        private readonly CertificationId $id,
        private CertificationName $name,
        private CertificationDescription $description,
        private CertificationStatus $status,
        private readonly CertificationDate $date,
        private ImageUrl $imageUrl,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        CertificationName $name,
        CertificationDescription $description,
        CertificationDate $date,
        ImageUrl $imageUrl,
    ): self {
        return new self(
            CertificationId::fromInt(0), // Se ignorará en BD (autoincrement)
            $name,
            $description,
            CertificationStatus::visible(),
            $date,
            $imageUrl,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $name,
        string $description,
        string $status,
        string $date,
        string $imageUrl,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            CertificationId::fromPrimitive($id),
            new CertificationName($name),
            new CertificationDescription($description),
            new CertificationStatus($status),
            new CertificationDate($date),
            new ImageUrl($imageUrl),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function activate(): void
    {
        $this->status = CertificationStatus::visible();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = CertificationStatus::hidden();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function update(
        ?string $name = null,
        ?string $description = null,
        ?string $imageUrl = null,
    ): void {
        if ($name !== null) {
            $this->name = new CertificationName($name);
        }
        if ($description !== null) {
            $this->description = new CertificationDescription($description);
        }
        if ($imageUrl !== null) {
            $this->imageUrl = new ImageUrl($imageUrl);
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): CertificationId
    {
        return $this->id;
    }

    public function Name(): CertificationName
    {
        return $this->name;
    }

    public function Description(): CertificationDescription
    {
        return $this->description;
    }

    public function Status(): CertificationStatus
    {
        return $this->status;
    }

    public function Date(): CertificationDate
    {
        return $this->date;
    }

    public function ImageUrl(): ImageUrl
    {
        return $this->imageUrl;
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
