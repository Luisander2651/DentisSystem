<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions\TestimonialException;

final readonly class TestimonialAuthor
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed) || strlen($trimmed) > 255) {
            throw TestimonialException::empty(static::class);
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
