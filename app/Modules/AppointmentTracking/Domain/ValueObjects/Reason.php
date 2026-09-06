<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\ReasonException;

final readonly class Reason
{
    private function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw ReasonException::emptyReason();
        }
    }

    public static function create(string $reason): self
    {

        return new self($reason);
    }

    public static function fromString(string $reason): self
    {
        return new self($reason);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
