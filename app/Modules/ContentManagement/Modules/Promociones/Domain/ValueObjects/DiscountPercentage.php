<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;

final readonly class DiscountPercentage
{
    public function __construct(
        public float $value,
    ) {
        if ($value < 0 || $value > 100) {
            throw PromotionException::outOfRange(static::class, $value, 0, 100);
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
