<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\MedicalDataIdException;

final readonly class MedicalDataId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw MedicalDataIdException::mustBeGreaterThanZero($id);
        }

        return new self($id);
    }
}

