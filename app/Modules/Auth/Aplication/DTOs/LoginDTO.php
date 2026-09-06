<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\DTOs;

final readonly class LoginDTO
{
    private function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function create(string $email, string $password): self
    {
        return new self(
            email: $email,
            password: $password,
        );
    }
}
