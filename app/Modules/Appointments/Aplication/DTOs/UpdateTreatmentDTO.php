<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class UpdateTreatmentDTO
{
    private function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $time = null,
    ) {}

    public static function create(
        string $id,
        ?string $name = null,
        ?string $description = null,
        ?string $time = null,
    ): self {
        return new self(
            id: trim($id),
            name: $name === null || $name === '' ? null : trim($name),
            description: $description === null || $description === '' ? null : trim($description),
            time: $time === null || $time === '' ? null : trim($time),
        );
    }

    public function hasValue(): bool
    {
        return $this->name !== null || $this->description !== null || $this->time !== null;
    }
}