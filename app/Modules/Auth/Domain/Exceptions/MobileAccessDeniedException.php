<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

final class MobileAccessDeniedException extends Exception
{
    public static function notAnAdministrator(): self
    {
        return new self('Only administrators can sign in to the mobile app.');
    }
}
