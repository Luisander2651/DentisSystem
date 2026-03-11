<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\AllergiesException;

final readonly class Allergies
{
    private function __construct(
        public ?array $value,
    ) {}

    public static function fromNullableArray(?array $allergies): self
    {
        if ($allergies === null) {
            return new self(null);
        }

        foreach ($allergies as $item) {
            if (!is_string($item)) {
                throw AllergiesException::allValuesMustBeStrings();
            }
        }

        return new self(array_values($allergies));
    }
}

