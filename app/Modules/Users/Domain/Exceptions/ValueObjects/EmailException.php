<?php

declare (strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

final class EmailException extends ValueObjectsException
{
    
    public static function invalidFormat(UserEmail $email): self
    {
        return new self("The email format is invalid: {$email->value}");
    }
}