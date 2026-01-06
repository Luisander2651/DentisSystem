<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\DTOs;

final readonly class SaveUserDTO
{
    private function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public string $roleId,
        public ?string $status = null,
    ) {}

    public static function create(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $roleId,
        ?string $status = null,
    ): self {
        return new self(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            roleId: $roleId,
            status: $status,
        );
    }
}