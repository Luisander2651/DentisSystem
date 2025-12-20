<?php

namespace App\Domain\Patient\Attributes;

final class PatientEmail
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: $value");
        }
    }
}