<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\TreatmentIdException;

final readonly class TreatmentId
{
    public function __construct(
        public string $value,
    ) {
        if (!is_numeric($value) || (int)$value <= 0) {
            throw TreatmentIdException::invalidValue($value);
        }
    }

    public static function fromInt(int $value): self
    {
        return new self((string)$value);
    }

    public function toInt(): int
    {
        return (int)$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
