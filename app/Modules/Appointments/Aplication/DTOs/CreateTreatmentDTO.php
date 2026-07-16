<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class CreateTreatmentDTO
{
    private function __construct(
        public string $name,
        public string $description,
        public string $time,
    ) {}

    public static function create(
        string $name,
        string $description,
        string $time,
    ): self {
        return new self(
            name: trim($name),
            description: trim($description),
            time: trim($time),
        );
    }
}