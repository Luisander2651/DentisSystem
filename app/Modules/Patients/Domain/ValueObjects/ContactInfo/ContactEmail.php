<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\ContactEmailException;

final readonly class ContactEmail
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $email): self
    {
        if ($email === null || trim($email) === '') {
            return new self(null);
        }

        $value = trim($email);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ContactEmailException::invalidFormat($value);
        }

        return new self($value);
    }
}

