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
        $formattedLast = self::formatName($lastName);

        $full = $formattedFirst.' '.$formattedLast;
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
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map(
            static function (string $word): string {
                $firstLetter = mb_strtoupper(mb_substr($word, 0, 1));
                $rest = mb_strtolower(mb_substr($word, 1));

                return $firstLetter.$rest;
            },
            $words,
        ));
    }

    public static function fromString(string $fullName): self
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $countedParts = count($parts);

        if ($countedParts < 2) {
            throw PatientNameException::invalidFormat($fullName);
        }

        if ($countedParts === 2) {
            $firstName = $parts[0];
            $lastName = $parts[1];
        } else {
            // Los últimos 2 tokens son siempre los apellidos; el resto (1 o más) son los nombres.
            $firstName = implode(' ', array_slice($parts, 0, -2));
            $lastName = implode(' ', array_slice($parts, -2));
        }

        return self::create($firstName, $lastName);
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
