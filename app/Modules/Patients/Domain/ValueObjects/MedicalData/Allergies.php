<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

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
                throw new \InvalidArgumentException('All allergies values must be strings.');
            }
        }

        return new self(array_values($allergies));
    }
}
