<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;

final readonly class ImageDescription
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $description): self
    {
        return new self($description);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
