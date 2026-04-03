<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs;

final readonly class UpdatePatientDTO
{
    private function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $status = null,
        public ?string $newPassword = null,
    ) {}

    public static function create(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $status = null,
        ?string $newPassword = null,
    ): self {
        return new self(
            firstName: ($firstName === '') ? null : $firstName,
            lastName: ($lastName === '') ? null : $lastName,
            status: ($status === '') ? null : $status,
            newPassword: ($newPassword === '') ? null : $newPassword,
        );
    }

    public function hasValue(): bool
    {
        return $this->firstName !== null ||
            $this->lastName !== null ||
            $this->status !== null ||
            $this->newPassword !== null;
    }
}
