<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialAuthor;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDescription;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;

use DateTimeImmutable;

final class TestimonialEntity
{
    private function __construct(
        private readonly TestimonialId $id,
        private TestimonialAuthor $author,
        private TestimonialDescription $description,
        private TestimonialStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        TestimonialAuthor $author,
        TestimonialDescription $description,
    ): self {
        return new self(
            TestimonialId::fromInt(0), // Se ignorará en BD (autoincrement)
            $author,
            $description,
            TestimonialStatus::visible(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $author,
        string $description,
        string $status,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new TestimonialId($id),
            new TestimonialAuthor($author),
            new TestimonialDescription($description),
            TestimonialStatus::fromString($status),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function update(
        ?string $author = null,
        ?string $description = null,
        ?string $status = null,
    ): void {
        if ($author !== null) {
            $this->author = new TestimonialAuthor($author);
        }

        if ($description !== null) {
            $this->description = new TestimonialDescription($description);
        }

        if ($status !== null) {
            $this->status = TestimonialStatus::fromString($status);
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    public function visible(): void
    {
        $this->status = TestimonialStatus::visible();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function hidden(): void
    {
        $this->status = TestimonialStatus::hidden();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): TestimonialId
    {
        return $this->id;
    }

    public function Author(): TestimonialAuthor
    {
        return $this->author;
    }

    public function Description(): TestimonialDescription
    {
        return $this->description;
    }

    public function Status(): TestimonialStatus
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
