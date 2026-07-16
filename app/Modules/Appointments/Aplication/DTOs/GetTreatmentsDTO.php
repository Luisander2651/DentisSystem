<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class GetTreatmentsDTO
{
    private function __construct(
        public ?string $id = null,
        public ?string $name = null,
    ) {}

    public static function create(?string $id = null, ?string $name = null): self
    {
        return new self(
            id: $id === null || $id === '' ? null : trim($id),
            name: $name === null || $name === '' ? null : trim($name),
        );
    }
}