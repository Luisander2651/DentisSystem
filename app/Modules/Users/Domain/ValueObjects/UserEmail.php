<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjects\EmailException;

final class UserEmail
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw EmailException::invalidFormat($this);
        }
    }

    // Create UserEmail from string (used when retrieving from DB)
    public static function fromString(string $email): self
    {
        return new self($email);
    }
}