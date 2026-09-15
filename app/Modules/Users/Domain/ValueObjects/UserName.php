<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjects\UserNameException;

final readonly class UserName
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
            throw UserNameException::invalidLength(
                fullName: trim($full),
                minLength: self::MIN_LENGTH,
                maxLength: self::MAX_LENGTH
            );
        }

        return new self($formattedFirst, $formattedLast);
    }

    /**
     * Finding 20, defect 1a: capitalises EVERY word, not just the first letter of the field.
     * The per-field rule turned 'Garcia Lopez' into 'Garcia lopez', so almost every staff
     * member with the two surnames usual in Mexico was shown wrongly. This is the same
     * correction Unit 2 applied to PatientName (BR-10).
     *
     * It also makes full() independent of where a first/last boundary is drawn, which is
     * what stopped the same doctor being written two different ways on two screens.
     */
    private static function formatName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map(
            static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)).mb_strtolower(mb_substr($word, 1)),
            $words,
        ));
    }

    /**
     * Splits a single string into first and last name: the last two words are the surnames
     * and everything before them is the first name - the rule PatientName already uses.
     *
     * The previous rule gave the first TWO words to the first name once there were four or
     * more, so a one- or three-word first name ended up with its boundary in the wrong place.
     *
     * Note that appointments no longer depend on this: they build the name from the separate
     * first_name and last_name columns (finding 20, option B), because those are already
     * stored apart and re-splitting a concatenation of them could only lose information.
     */
    public static function fromString(string $fullName): self
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2) {
            throw UserNameException::invalidFormat($fullName);
        }

        if (count($parts) === 2) {
            return self::create($parts[0], $parts[1]);
        }

        return self::create(
            implode(' ', array_slice($parts, 0, -2)),
            implode(' ', array_slice($parts, -2)),
        );
    }

    /**
     * Trimmed, so a record with an empty surname - reachable only for data created before
     * BR-12 made last_name required - does not render with a trailing space.
     */
    public function full(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function __toString(): string
    {
        return $this->full();
    }
}
