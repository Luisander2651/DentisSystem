<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\PostalCodeException;

final readonly class PostalCode
{
    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $postalCode): self
    {
        if ($postalCode === null || trim($postalCode) === '') {
            return new self(null);
        }

        $value = trim($postalCode);
        if (!preg_match('/^[A-Za-z0-9\-\s]{3,12}$/', $value)) {
            throw PostalCodeException::invalidFormat($value);
        }

        return new self($value);
    }
}

