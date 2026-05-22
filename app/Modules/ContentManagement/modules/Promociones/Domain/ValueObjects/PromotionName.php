<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class PromotionName
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

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
