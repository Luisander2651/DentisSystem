<?php

namespace App\Domain\User;

final class UserId
{
    public function __construct(
        public readonly int $value,
    ) {}
}