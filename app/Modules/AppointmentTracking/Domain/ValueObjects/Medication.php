<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\MedicationException;

final readonly class Medication
{
    /** Spec 020 (R3): the size of its column. */
    public const MAX_LENGTH = 255;

    private function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw MedicationException::emptyMedication();
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw MedicationException::tooLong(self::MAX_LENGTH);
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
