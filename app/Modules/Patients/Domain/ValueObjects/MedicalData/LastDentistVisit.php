<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

final readonly class LastDentistVisit
{
    private function __construct(
        public ?array $value,
    ) {}

    public static function fromNullableArray(?array $lastDentistVisit): self
    {
        if ($lastDentistVisit === null) {
            return new self(null);
        }

        return new self($lastDentistVisit);
    }
}

