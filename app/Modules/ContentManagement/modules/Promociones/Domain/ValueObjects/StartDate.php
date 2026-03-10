<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Promociones\Domain\ValueObjects;

use InvalidArgumentException;
use DateTime;

final readonly class StartDate
{
    public function __construct(
        public string $value,
    ) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException(sprintf("<%s> does not allow the value <%s>. Expected format: Y-m-d", static::class, $value));
        }
    }

    public static function fromString(string $dateString): self
    {
        return new self($dateString);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
