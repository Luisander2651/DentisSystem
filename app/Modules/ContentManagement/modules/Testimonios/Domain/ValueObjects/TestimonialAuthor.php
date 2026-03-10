<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Testimonios\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class TestimonialAuthor
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed) || strlen($trimmed) > 255) {
            throw new InvalidArgumentException(
                sprintf("<%s> must be a non-empty string up to 255 characters.", static::class)
            );
        }
    }

    public static function fromString(string $author): self
    {
        return new self($author);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
