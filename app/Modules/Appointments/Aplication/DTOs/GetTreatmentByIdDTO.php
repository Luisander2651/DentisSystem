<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class GetTreatmentByIdDTO
{
    private function __construct(
        public string $id,
    ) {}

    public static function create(string $id): self
    {
        return new self(id: trim($id));
    }
}