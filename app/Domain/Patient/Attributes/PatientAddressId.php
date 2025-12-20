<?php

namespace App\Domain\Patient\Attributes;

final class AddressId
{
    public function __construct(
        public int $value,
    ) {}
}