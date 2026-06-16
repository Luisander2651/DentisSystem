<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects;

final class GalleryStatus
{
    public const VISIBLE = 'visible';
    public const HIDDEN = 'hidden';

    private function __construct(public readonly string $value) {}

    public static function visible(): self
    {
        return new self(self::VISIBLE);
    }

    public static function hidden(): self
    {
        return new self(self::HIDDEN);
    }

    public static function fromString(string $status): self
    {
        $status = strtolower($status);
        if (!in_array($status, [self::VISIBLE, self::HIDDEN], true)) {
            throw new \InvalidArgumentException("Invalid gallery status: $status");
        }
        return new self($status);
    }
}