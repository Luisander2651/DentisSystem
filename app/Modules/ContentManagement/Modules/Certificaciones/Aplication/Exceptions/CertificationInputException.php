<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions;

use Exception;

final class CertificationInputException extends Exception
{
    public static function missingRequiredFields(): self
    {
        return new self('Todos los campos son obligatorios.');
    }

    public static function atLeastOneFieldRequired(): self
    {
        return new self('Al menos un campo debe ser proporcionado para la actualización.');
    }
}