<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\TreatmentDescriptionException;

final readonly class TreatmentDescription
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw TreatmentDescriptionException::emptyDescription();
        }
    }

    public static function fromString(string $description): self
    {
        return new self($description);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
