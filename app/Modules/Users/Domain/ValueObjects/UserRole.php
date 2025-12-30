<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

final class UserRole
{
    private const ADMIN = 'admin';
    private const DOCTOR = 'doctor';
    private const ASISTENT = 'asistent';

    private const VALID_ROLES = [
        self::ADMIN,
        self::DOCTOR,
        self::ASISTENT,
    ];

    public function __construct(
        public readonly string $value,
    ) {
        if (!in_array($this->value, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException("Invalid user role: {$this->value}");
        }
    }

    //Generated methods for convenience
    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function doctor(): self
    {
        return new self(self::DOCTOR);
    }

    public static function asistent(): self
    {
        return new self(self::ASISTENT);
    }

    // Create UserRole from string (used when retrieving from DB)
    public static function fromString(string $role): self
    {
        return new self($role);
    }

    // Validation methods
    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isDoctor(): bool
    {
        return $this->value === self::DOCTOR;
    }

    public function isAsistent(): bool
    {
        return $this->value === self::ASISTENT;
    }
}