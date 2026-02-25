<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

final readonly class AddressId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Address id must be greater than zero.');
        }

        return new self($id);
    }
}
