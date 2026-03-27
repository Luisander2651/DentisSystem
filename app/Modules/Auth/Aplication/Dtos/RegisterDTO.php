<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\DTOs;

final readonly class RegisterDTO
{
    private function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public string $confirmPassword,
    ) {}

    public static function create(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $confirmPassword,
    ): self {
        return new self(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            confirmPassword: $confirmPassword,
        );
    }
}
