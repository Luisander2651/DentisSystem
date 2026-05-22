<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CertificationStatus
{
    private const VALID_STATUSES = ['visible', 'oculto'];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    "<%s> does not allow the value <%s>. Valid values: %s",
                    static::class,
                    $value,
                    implode(', ', self::VALID_STATUSES)
                )
            );
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

    public function isActive(): bool
    {
        return $this->value === 'visible';
    }

    public function isInactive(): bool
    {
        return $this->value === 'oculto';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
