<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

final readonly class ContactInfoId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Contact info id must be greater than zero.');
        }

        return new self($id);
    }
}
