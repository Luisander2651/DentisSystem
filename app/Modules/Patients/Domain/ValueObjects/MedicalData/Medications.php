<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\MedicationsException;

final readonly class Medications
{
    private function __construct(
        public ?array $value,
    ) {}

    public static function fromNullableArray(?array $medications): self
    {
        if ($medications === null) {
            return new self(null);
        }

        foreach ($medications as $item) {
            if (!is_string($item)) {
                throw MedicationsException::allValuesMustBeStrings();
            }
        }

        return new self(array_values($medications));
    }
}

