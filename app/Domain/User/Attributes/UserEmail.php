<?php

namespace App\Domain\User;

final class UserEmail
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: $value");
        }
    }
}