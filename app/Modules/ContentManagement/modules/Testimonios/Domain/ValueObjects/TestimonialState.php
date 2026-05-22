<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TestimonialState
{
    private const VALID_STATES = ['draft', 'published', 'archived'];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    "<%s> does not allow the value <%s>. Valid values: %s",
                    static::class,
                    $value,
                    implode(', ', self::VALID_STATES)
                )
            );
        }
    }

    public static function draft(): self
    {
        return new self('draft');
    }

    public static function published(): self
    {
        return new self('published');
    }

    public static function archived(): self
    {
        return new self('archived');
    }

    public static function fromString(string $state): self
    {
        return new self($state);
    }

    public function isDraft(): bool
    {
        return $this->value === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->value === 'published';
    }

    public function isArchived(): bool
    {
        return $this->value === 'archived';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
