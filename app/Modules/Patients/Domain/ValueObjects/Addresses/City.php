<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\CityException;

final readonly class City
{
    private const MAX_LENGTH = 100;

    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $city): self
    {
        if ($city === null || trim($city) === '') {
            return new self(null);
        }

        $value = trim($city);
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw CityException::tooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }
}

