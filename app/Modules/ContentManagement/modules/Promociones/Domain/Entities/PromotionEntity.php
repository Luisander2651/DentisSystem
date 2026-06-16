<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\Entities;

use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionDescription;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\DiscountPercentage;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\StartDate;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\EndDate;

use DateTimeImmutable;
use DateTime;

final class PromotionEntity
{
    private function __construct(
        private readonly PromotionId $id,
        private PromotionName $name,
        private PromotionDescription $description,
        private PromotionStatus $status,
        private DiscountPercentage $discountPercentage,
        private StartDate $startDate,
        private EndDate $endDate,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        PromotionName $name,
        PromotionDescription $description,
        DiscountPercentage $discountPercentage,
        StartDate $startDate,
        EndDate $endDate,
    ): self {
        return new self(
            PromotionId::fromInt(0), // Se ignorará en BD (autoincrement)
            $name,
            $description,
            PromotionStatus::visible(),
            $discountPercentage,
            $startDate,
            $endDate,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $name,
        string $description,
        string $status,
        float $discountPercentage,
        string $startDate,
        string $endDate,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new PromotionId($id),
            new PromotionName($name),
            new PromotionDescription($description),
            PromotionStatus::fromString($status),
            new DiscountPercentage($discountPercentage),
            new StartDate($startDate),
            new EndDate($endDate),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function activate(): void
    {
        $this->status = PromotionStatus::visible();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = PromotionStatus::hidden();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->status->isVisible();
    }

    public function isExpired(): bool
    {
        $today = new DateTime();
        $endDate = DateTime::createFromFormat('Y-m-d', $this->endDate->value);
        return $today > $endDate;
    }

    public function update(
        ?string $name = null,
        ?string $description = null,
        ?float $discountPercentage = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $status = null,
    ): void {
        if ($name !== null) {
            $this->name = new PromotionName($name);
        }
        if ($description !== null) {
            $this->description = new PromotionDescription($description);
        }
        if ($discountPercentage !== null) {
            $this->discountPercentage = new DiscountPercentage($discountPercentage);
        }
        if ($startDate !== null) {
            $this->startDate = new StartDate($startDate);
        }
        if ($endDate !== null) {
            $this->endDate = new EndDate($endDate);
        }
        if ($status !== null) {
            $this->status = PromotionStatus::fromString($status);
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): PromotionId
    {
        return $this->id;
    }

    public function Name(): PromotionName
    {
        return $this->name;
    }

    public function Description(): PromotionDescription
    {
        return $this->description;
    }

    public function Status(): PromotionStatus
    {
        return $this->status;
    }

    public function DiscountPercentage(): DiscountPercentage
    {
        return $this->discountPercentage;
    }

    public function StartDate(): StartDate
    {
        return $this->startDate;
    }

    public function EndDate(): EndDate
    {
        return $this->endDate;
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
