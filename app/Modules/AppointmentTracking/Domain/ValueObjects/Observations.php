<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

final readonly class Observations
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $observations): self
    {
        if ($observations === null || trim($observations) === '') {
            return new self(null);
        }

        return new self($observations);
    }
}
