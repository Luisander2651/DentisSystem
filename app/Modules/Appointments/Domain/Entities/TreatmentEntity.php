<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Entities;

use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentDescription;

use DateTimeImmutable;

final class TreatmentEntity
{
    private function __construct(
        private readonly TreatmentId $id,
        private TreatmentName $name,
        private TreatmentDescription $description,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        TreatmentName $name,
        TreatmentDescription $description,
    ): self {
        return new self(
            TreatmentId::fromInt(0), // Se ignorará en BD (autoincrement)
            $name,
            $description,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $name,
        string $description,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new TreatmentId($id),
            new TreatmentName($name),
            new TreatmentDescription($description),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function update(
        ?string $name = null,
        ?string $description = null,
    ): void {
        if ($name !== null) {
            $this->name = new TreatmentName($name);
        }
        if ($description !== null) {
            $this->description = new TreatmentDescription($description);
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): TreatmentId
    {
        return $this->id;
    }

    public function Name(): TreatmentName
    {
        return $this->name;
    }

    public function Description(): TreatmentDescription
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
