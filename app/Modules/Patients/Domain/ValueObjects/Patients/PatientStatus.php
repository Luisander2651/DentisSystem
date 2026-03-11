<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PatientStatusException;

final class PatientStatus
{
    private const ACTIVE = 'active';
    private const INACTIVE = 'inactive';

    private const VALID_STATUSES = [
        self::ACTIVE,
        self::INACTIVE,
    ];

    private function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw PatientStatusException::invalidStatus($this);
        }
    }

    public static function create(): self
    {
        return new self(self::ACTIVE);
    }

    // Generated methods for convenience
    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public function value(): string
    {
        return $this->value;
    }

    // Create UserStatus from string (used when retrieving from DB)
    public static function fromString(string $status): self
    {
        return new self($status);
    }

    // Validation methods
    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->value === self::INACTIVE;
    }
}

