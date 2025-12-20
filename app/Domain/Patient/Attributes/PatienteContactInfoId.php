<?php

namespace App\Domain\Patient\Attributes;

final class ContactInfoId
{
    public function __construct(
        public int $value,
    ) {}
}