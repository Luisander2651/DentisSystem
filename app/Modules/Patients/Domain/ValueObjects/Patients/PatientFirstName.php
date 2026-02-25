<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

final readonly class PatientFirstName
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 50;

    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $firstName): self
    {
        $value = trim($firstName);
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Invalid patient first name length.');
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
