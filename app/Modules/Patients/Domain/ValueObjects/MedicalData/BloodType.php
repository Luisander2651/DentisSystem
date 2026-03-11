<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\BloodTypeException;

final readonly class BloodType
{
    private const VALID_TYPES = [
        'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-',
    ];

    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $bloodType): self
    {
        if ($bloodType === null || trim($bloodType) === '') {
            return new self(null);
        }

        $value = strtoupper(trim($bloodType));
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw BloodTypeException::invalidType($value);
        }

        return new self($value);
    }
}

