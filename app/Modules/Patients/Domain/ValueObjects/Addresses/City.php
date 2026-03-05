<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

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
            throw new \InvalidArgumentException('City is too long.');
        }

        return new self($value);
    }
}
