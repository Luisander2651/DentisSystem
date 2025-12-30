<?php

declare(strict_types=1);

namespace App\Core\Domain;

use Illuminate\Support\Str;
use InvalidArgumentException;

abstract readonly class UuidIdentifier
{
    public function __construct(public string $value)
    {
        $this->ensureIsValidUuid($value);
    }

    public static function random(): static
    {
        // Centralizamos el uso de la librería de Laravel (o Ramsey\Uuid) aquí
        return new static((string) Str::uuid());
    }

    private function ensureIsValidUuid(string $value): void
    {
        if (!Str::isUuid($value)) {
            throw new InvalidArgumentException(sprintf("<%s> does not allow the value <%s>.", static::class, $value));
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}