<?php

declare (strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PatientRoleException;

final class PatientRole
{
    public const ROLE_PATIENT = 'patient';
    
    private function __construct(
        public readonly string $value
    ) {
        if (!in_array($value, [self::ROLE_PATIENT], true)) {
            throw PatientRoleException::invalidRole($this);
        }
    }

    public static function patient(): self
    {
        return new self(self::ROLE_PATIENT);
    }

    public function value(): string
    {
        return $this->value;
    }

    // Create PatientRole from string (used when retrieving from DB)
    public static function fromString(string $role): self
    {
        return new self($role);
    }
}
