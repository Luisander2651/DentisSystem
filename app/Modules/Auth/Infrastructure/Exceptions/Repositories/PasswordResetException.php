<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Exceptions\Repositories;

use Exception;

final class PasswordResetException extends Exception
{
    public static function UserNotFound(): self
    {
        return new self('Usuario no encontrado.');
    }

    public static function PatientNotFound(): self
    {
        return new self('Paciente no encontrado.');
    }
}