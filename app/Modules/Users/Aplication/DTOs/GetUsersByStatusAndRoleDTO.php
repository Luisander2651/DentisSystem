<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\DTOs;

final readonly class GetUsersByStatusAndRoleDTO
{
    private function __construct(
        public ?string $status = null,
        public ?string $role= null,
    ){}

    public static function create(
        ?string $status = null,
        ?string $role= null,
    ): self {
        return new self (
            status: ($status === '') ? null : $status,
            role: ($role === '') ? null : $role,
        );
    }

    public function hasValue(): bool
    {
        return $this->role !== null ||
               $this->status !== null;
    }
}