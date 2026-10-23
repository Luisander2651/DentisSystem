<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

final readonly class Instructions
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function create(?string $instructions): self
    {
        return self::fromNullable($instructions);
    }

    public static function fromNullable(?string $instructions): self
    {
        if ($instructions === null || trim($instructions) === '') {
            return new self(null);
        }

        return new self($instructions);
    }
}
