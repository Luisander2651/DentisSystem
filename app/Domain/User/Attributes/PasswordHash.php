<?php

namespace App\Domain\User;

final class PasswordHash
{
    public function __construct(
        public readonly string $value,
    ) {
        if (strlen($value) < 60) {
            throw new \InvalidArgumentException("Invalid password hash format.");
        }
    }
}