<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;

final readonly class PromotionStatus
{
    private const VALID_STATUSES = ['visible', 'oculto'];

    private function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw PromotionException::invalidValue(static::class, $value);
        }
    }

    public static function default(): self
    {
        return new self('visible');
    }

    public static function visible(): self
    {
        return new self('visible');
    }

    public static function hidden(): self
    {
        return new self('oculto');
    }

    public static function fromString(string $status): self
    {
        return new self($status);
    }

    public function isVisible(): bool
    {
        return $this->value === 'visible';
    }

    public function isHidden(): bool
    {
        return $this->value === 'oculto';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
