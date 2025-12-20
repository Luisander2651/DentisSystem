<?php

namespace App\Domain\Patient\Attributes;

final class RoleId
{
    public function __construct(
        public int $value,
    ) {}
}