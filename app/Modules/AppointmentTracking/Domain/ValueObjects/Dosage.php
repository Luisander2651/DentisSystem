<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\DosageException;

final readonly class Dosage
{
    /** Spec 020 (R3): the size of its column. */
    public const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw DosageException::emptyDosage();
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw DosageException::tooLong(self::MAX_LENGTH);
        }
    }

    public static function create(string $dosage): self
    {
        return new self($dosage);
    }

    public static function fromString(string $dosage): self
    {
        return new self($dosage);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
