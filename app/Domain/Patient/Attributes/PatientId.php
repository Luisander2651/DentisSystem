<?php

namespace App\Domain\Patient\Attributes;

final class PatientId
{
    public function __construct(
        public int $value,
    ) {}
}