<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\DiagnosisException;

final readonly class Diagnosis
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw DiagnosisException::emptyDiagnosis();
        }
    }

    public static function create(string $diagnosis): self
    {
        return new self($diagnosis);
    }

    public static function fromString(string $diagnosis): self
    {
        return new self($diagnosis);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
