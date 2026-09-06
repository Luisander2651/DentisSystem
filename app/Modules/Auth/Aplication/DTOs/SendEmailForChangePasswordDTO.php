<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\DTOs;

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;

class SendEmailForChangePasswordDTO
{
    public function __construct(
        public string $email,
    ) {
        if (empty($email)) {
            throw AuthAplicationExceptions::invalidCredentials();
        }
    }
}