<?php

namespace App\Modules\Users\Aplication\Exceptions;

use Exception;

final class UserAplicationExceptions extends Exception
{
    public static function NoInfoRetrivered(): self 
    {
        return new self('At least one field must be provided for update.');
    }
}