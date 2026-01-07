<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserRoleException;

final class UserRoleId
{
    private const ADMIN = 'admin';
    private const ASISTENT = 'asistent';
    private const DOCTOR = 'doctor';

    private const VALID_ROLES = [
        self::ADMIN,
        self::DOCTOR,
        self::ASISTENT,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_ROLES, true)) {
            throw UserRoleException::invalidFormat($this);
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

    public function toDatabaseId(): int
    {
        if ($this->isAdmin()) {
            return 1;
        } elseif ($this->isAsistent()) {
            return 2;
        } elseif ($this->isDoctor()) {
            return 3;
        }

        throw UserRoleException::notFound($this);
    }

    public static function fromDatabaseId(string $id): self
    {
        return match ((int)$id) {
            1 => self::admin(),
            2 => self::asistent(),
            3 => self::doctor(),
            default => throw UserRoleException::invalidFormat(new self($id)),
        };
    }
}