<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;
use App\Modules\Appointments\Domain\Exceptions\ValueObjects\TreatmentTimeException;
final class TreatmentTime
{
    private function __construct(
        public readonly int $value,
    ) {
        if ($this->value < 0 || $this->value > 240) {
            throw TreatmentTimeException::invalidTime(0, 240);
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}