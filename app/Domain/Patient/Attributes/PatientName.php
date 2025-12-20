<?php

namespace App\Domain\Patient\Attributes;

final class PatientName
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {}
}