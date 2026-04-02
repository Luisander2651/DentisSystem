<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\DTOs;

final readonly class UpdateUserDto
{
    private function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $roleId,
        public ?string $status = null,
        public ?string $newPassword = null,
    ) {}

    public static function create(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $roleId = null,
        ?string $status = null,
        ?string $newPassword = null,
    ): self {
        return new self(
            firstName: ($firstName === '') ? null : $firstName,
            lastName: ($lastName === '') ? null : $lastName,
            roleId: ($roleId === '') ? null : $roleId,
            status: ($status === '') ? null : $status,
            newPassword: ($newPassword === '') ? null : $newPassword,
        );
    }

    public function hasValue(): bool
    {
        return $this->firstName !== null ||
               $this->lastName !== null ||
               $this->roleId !== null ||
               $this->status !== null ||
               $this->newPassword !== null;
    }
}