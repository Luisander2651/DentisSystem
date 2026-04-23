<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentTimeException;
use DateTime;

final readonly class AppointmentTime
{
    public function __construct(
        public string $value,
    ) {
        $time = DateTime::createFromFormat('H:i', $value);
        if (!$time || $time->format('H:i') !== $value) {
            throw AppointmentTimeException::invalidFormat($value, 'H:i');
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
