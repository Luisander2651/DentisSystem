<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients\PasswordException;

final class PasswordHash
{
    private function __construct(
        public readonly string $value,
    ) {
        if (strlen($value) !== 60) {
            throw PasswordException::invalidFormat();
        }

        $hashInfo = password_get_info($value);
        if ($hashInfo['algo'] !== PASSWORD_BCRYPT) {
            throw PasswordException::invalidFormat();
        }
    }

    public static function createFromPlainText(string $plainPassword): self
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw PasswordException::failedToHash();
        }

        return new self($hash);
    }

    /**
     * Verifica si una contraseña plana coincide con este hash
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }

    // Create PasswordHash from string (used when retrieving from DB)
    public static function fromString(string $hash): self
    {
        return new self($hash);
    }
}
