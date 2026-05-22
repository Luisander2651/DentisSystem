<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions\TestimonialException;

final readonly class TestimonialStatus
{
    private const VALID_STATUSES = ['visible', 'oculto'];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw TestimonialException::invalidValue(static::class, $value);
        }
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
