<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs;

final readonly class CreatePatientDTO
{
    private function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
    ) {}

    public static function create(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
    ): self {
        return new self(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
        );
    }
}
