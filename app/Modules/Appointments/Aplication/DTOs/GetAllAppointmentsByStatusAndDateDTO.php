<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class GetAllAppointmentsByStatusAndDateDTO
{
    public function __construct(
        public ?string $status,
        public ?string $date,
    ) {}

    public static function create(
        ?string $status,
        ?string $date,
    ): self
    {
        return new self(
            status: $status,
            date: $date,
        );
    }
}