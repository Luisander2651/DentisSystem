<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

final readonly class Recommendations
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $recommendations): self
    {
        if ($recommendations === null || trim($recommendations) === '') {
            return new self(null);
        }

        return new self($recommendations);
    }
}
