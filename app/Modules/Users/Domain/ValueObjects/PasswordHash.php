<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

final class PasswordHash
{
    public function __construct(
        public readonly string $value,
    ) {
        if (strlen($value) !== 60) {
            throw new \InvalidArgumentException("Invalid password hash length.");
        }

        $hashInfo = password_get_info($value);
        if ($hashInfo['algo'] !== PASSWORD_BCRYPT) {
            throw new \InvalidArgumentException("Invalid password hash format. Must be a valid bcrypt hash.");
        }
    }

    // Create PasswordHash from string (used when retrieving from DB)
    public static function fromString(string $hash): self
    {
        return new self($hash);
    }
}