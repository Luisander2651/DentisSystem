<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

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
            throw new \InvalidArgumentException('Invalid phone number format.');
        }

        return new self($value);
    }
}
