<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\EmailException;

final class PatientEmail
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw EmailException::invalidFormat($this);
        }
    }

    // Create PatientEmail from string (used when retrieving from DB)
    public static function fromString(string $email): self
    {
        return new self($email);
    }
}
