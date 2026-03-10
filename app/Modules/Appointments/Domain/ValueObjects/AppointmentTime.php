<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use InvalidArgumentException;
use DateTime;

final readonly class AppointmentTime
{
    public function __construct(
        public string $value,
    ) {
        $time = DateTime::createFromFormat('H:i', $value);
        if (!$time || $time->format('H:i') !== $value) {
            throw new InvalidArgumentException(sprintf("<%s> does not allow the value <%s>. Expected format: H:i", static::class, $value));
        }
    }

    public static function fromString(string $timeString): self
    {
        return new self($timeString);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
