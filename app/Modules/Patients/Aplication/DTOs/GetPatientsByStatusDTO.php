<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs;

final readonly class GetPatientsByStatusDTO
{
    private function __construct(
        public ?string $status = null,
    ) {}

    public static function create(
        ?string $status = null,
    ): self {
        return new self(
            status: ($status === '') ? null : $status,
        );
    }
}