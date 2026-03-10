<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Galeria\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class GalleryImageId
{
    public function __construct(
        public string $value,
    ) {
        if (!is_numeric($value) || (int)$value <= 0) {
            throw new InvalidArgumentException(sprintf("<%s> does not allow the value <%s>.", static::class, $value));
        }
    }

    public static function fromInt(int $value): self
    {
        return new self((string)$value);
    }

    public function toInt(): int
    {
        return (int)$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
