<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\MedicationException;

final readonly class Medication
{
    private function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw MedicationException::emptyMedication();
        }
    }

    public static function create(string $medication): self
    {
        return new self($medication);
    }

    public static function fromString(string $medication): self
    {
        return new self($medication);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
