<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\StateException;

final readonly class State
{
    private const MAX_LENGTH = 100;

    private function __construct(
        public ?string $value,
    ) {}

    public static function fromNullable(?string $state): self
    {
        if ($state === null || trim($state) === '') {
            return new self(null);
        }

        $value = trim($state);
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw StateException::tooLong(self::MAX_LENGTH);
        }

        return new self($value);
    }
}

