<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;

final readonly class ImageUrl
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw CertificationException::empty(static::class);
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
