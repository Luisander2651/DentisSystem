<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;

final readonly class GalleryImageUrl
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw GalleryImageException::empty(static::class);
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
