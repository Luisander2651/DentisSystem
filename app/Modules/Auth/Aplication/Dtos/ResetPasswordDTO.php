<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\DTOs;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $newPassword,
    ) {}
}