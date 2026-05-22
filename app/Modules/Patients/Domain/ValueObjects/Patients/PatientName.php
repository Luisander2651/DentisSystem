<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PatientNameException;

final readonly class PatientName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    private function __construct(
        public string $firstName,
        public string $lastName,
    ) {}

    public static function create(string $firstName, string $lastName): self
    {

        $formattedFirst = self::formatName($firstName);
        $formattedLast  = self::formatName($lastName);


        $full = $formattedFirst . ' ' . $formattedLast;
        $length = mb_strlen(trim($full));

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw PatientNameException::invalidLength(
                name: new self($formattedFirst, $formattedLast),
                minLength: self::MIN_LENGTH,
                maxLength: self::MAX_LENGTH
            );
        }

        return new self($formattedFirst, $formattedLast);
    }

    private static function formatName(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '';

        $firstLetter = mb_strtoupper(mb_substr($name, 0, 1));
        $rest = mb_strtolower(mb_substr($name, 1));

        return $firstLetter . $rest;
    }

    public static function fromString(string $fullName): self
    {
        $parts = preg_split('/\s+/', trim($fullName), 4, PREG_SPLIT_NO_EMPTY) ?: [];

        $countedParts = count($parts);

        if ($countedParts < 2) {
            throw PatientNameException::invalidFormat($fullName);
        }

        if ($countedParts === 2) {
            $firstName = self::formatName($parts[0]);
            $lastName = self::formatName($parts[1]);
        } elseif ($countedParts === 3) {
            $firstName = self::formatName($parts[0]);
            $lastName = self::formatName($parts[1] . ' ' . $parts[2]);
        } else {
            $firstName = self::formatName($parts[0] . ' ' . $parts[1]);
            $lastName = self::formatName($parts[2] . ' ' . $parts[3]);
        }

        return new self($firstName, $lastName);
    }

    public function full(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function __toString(): string
    {
        return $this->full();
    }
}
