<?php

namespace App\Domain\Patient\Attributes;

final class PatientPhone
{
    public function __construct(
        public string $value,
    ) {
        if (!preg_match('/^\+?[0-9]{7,15}$/', $value)) {
            throw new \InvalidArgumentException("Invalid phone number format: $value");
        }
    }
}