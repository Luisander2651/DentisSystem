<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

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
            throw new \InvalidArgumentException('Street is too long.');
        }

        return new self($value);
    }
}
