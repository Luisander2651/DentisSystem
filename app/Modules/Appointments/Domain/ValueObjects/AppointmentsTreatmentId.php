<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentsTreatmentIdException;

final readonly class AppointmentsTreatmentId
{
    private function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw AppointmentsTreatmentIdException::emptyValue();
        }

        if (!ctype_digit($value)) {
            throw AppointmentsTreatmentIdException::invalidIntegerString($value);
        }

        if ((int)$value <= 0) {
            throw AppointmentsTreatmentIdException::nonPositiveValue($value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}