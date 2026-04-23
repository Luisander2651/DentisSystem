<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\TreatmentNameException;

final readonly class TreatmentName
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed) || strlen($trimmed) > 255) {
            throw TreatmentNameException::invalidLength(255);
        }
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
