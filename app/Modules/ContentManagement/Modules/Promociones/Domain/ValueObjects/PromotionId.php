<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;

final readonly class PromotionId
{
    public function __construct(
        public string $value,
    ) {
        if (!is_numeric($value)) {
            throw PromotionException::invalidValue(static::class, $value);
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
