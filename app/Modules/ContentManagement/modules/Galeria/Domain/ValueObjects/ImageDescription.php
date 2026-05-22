<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ImageDescription
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException(
                sprintf("<%s> must be a non-empty string.", static::class)
            );
        }
    }

    public static function fromString(string $description): self
    {
        return new self($description);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
