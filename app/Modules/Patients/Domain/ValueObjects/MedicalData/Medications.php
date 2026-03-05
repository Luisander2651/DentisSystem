<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

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
                throw new \InvalidArgumentException('All medications values must be strings.');
            }
        }

        return new self(array_values($medications));
    }
}
