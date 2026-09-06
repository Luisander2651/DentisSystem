<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

final readonly class Instructions
{
    private function __construct(
        public ?string $value,
    ) {
        if ($value === null || trim($value) === '') {
            $this->value = null;
        }
    }

    public static function create(?string $instructions): self
    {
        return new self($instructions);
    }

    public static function fromNullable(?string $instructions): self
    {
        return new self($instructions);
    }
}
