<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\PhoneNumberException;

final readonly class PhoneNumber
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $phoneNumber): self
    {
        if ($phoneNumber === null || trim($phoneNumber) === '') {
            return new self(null);
        }

        $value = trim($phoneNumber);
        if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $value)) {
            throw PhoneNumberException::invalidFormat($value);
        }

        return new self($value);
    }
}

