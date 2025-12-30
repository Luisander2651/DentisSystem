<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

final readonly class UserStatus
{
    private const ACTIVE = 'active';
    private const INACTIVE = 'inactive';
    private const SUSPENDED = 'suspended';

    private const VALID_STATUSES = [
        self::ACTIVE,
        self::INACTIVE,
        self::SUSPENDED,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid user status: {$this->value}");
        }
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

    public static function suspended(): self
    {
        return new self(self::SUSPENDED);
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

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }
}