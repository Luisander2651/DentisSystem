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
        $acceptedFormats = ['H:i', 'H:i:s'];

        foreach ($acceptedFormats as $format) {
            $time = DateTime::createFromFormat($format, $value);

            if ($time && $time->format($format) === $value) {
                return;
            }
        }

        throw AppointmentTimeException::invalidFormat($value, 'H:i or H:i:s');
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
