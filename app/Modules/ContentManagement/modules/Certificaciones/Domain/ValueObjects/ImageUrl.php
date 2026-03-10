<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Certificaciones\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ImageUrl
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException(
                sprintf("<%s> must be a non-empty string.", static::class)
            );
        }
    }

    public static function fromString(string $url): self
    {
        return new self($url);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
