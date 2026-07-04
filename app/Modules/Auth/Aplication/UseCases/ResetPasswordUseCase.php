<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Domain\Service\PasswordResetService;
use App\Modules\Auth\Aplication\DTOs\ResetPasswordDTO;
final readonly class ResetPasswordUseCase
{
    public function __construct(
        private PasswordResetService $passwordResetService,
    ) {}

    public function execute(ResetPasswordDTO $dto): void
    {
        $this->passwordResetService->resetPassword($dto->token, $dto->newPassword);
    }
}