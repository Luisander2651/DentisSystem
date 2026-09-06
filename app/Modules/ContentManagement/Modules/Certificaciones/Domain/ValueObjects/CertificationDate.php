<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;
use DateTime;

final readonly class CertificationDate
{
    public function __construct(
        public string $value,
    ) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw CertificationException::invalidFormat(static::class, $value, 'Y-m-d');
        }
    }

    public static function fromString(string $dateString): self
    {
        return new self($dateString);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
