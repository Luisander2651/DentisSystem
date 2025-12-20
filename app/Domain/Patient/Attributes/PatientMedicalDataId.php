<?php

namespace App\Domain\Patient\Attributes;

final class MedicalDataId
{
    public function __construct(
        public int $value,
    ) {}
}