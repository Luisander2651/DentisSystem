<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Promociones\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class DiscountPercentage
{
    public function __construct(
        public float $value,
    ) {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException(
                sprintf("<%s> must be between 0 and 100. Got: %s", static::class, $value)
            );
        }
    }

    public static function fromFloat(float $percentage): self
    {
        return new self($percentage);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
