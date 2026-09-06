<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;

final readonly class CertificationId
{
    private function __construct(
        public string $value,
    ) {
        if (!is_numeric($value)) {
            throw CertificationException::invalidValue(static::class, $value);
        }
    }

    public static function fromInt(int $value): self
    {

        return new self((string)$value);
    }

    public static function fromPrimitive(string $value): self
    {
        if ($value <= 0) {
            throw CertificationException::invalidValue(static::class, $value);
        }
        return new self($value);
    }

    public function toInt(): int
    {
        return (int)$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
