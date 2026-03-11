<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\EmergencyContactException;

final readonly class EmergencyContact
{
    private const MAX_LENGTH = 120;

    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $emergencyContact): self
    {
        if ($emergencyContact === null || trim($emergencyContact) === '') {
            return new self(null);
        }

        $value = trim($emergencyContact);
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw EmergencyContactException::tooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }
}

