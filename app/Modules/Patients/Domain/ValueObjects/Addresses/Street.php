<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\StreetException;

final readonly class Street
{
    private const MAX_LENGTH = 150;

    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $street): self
    {
        if ($street === null || trim($street) === '') {
            return new self(null);
        }

        $value = trim($street);
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw StreetException::tooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }
}

