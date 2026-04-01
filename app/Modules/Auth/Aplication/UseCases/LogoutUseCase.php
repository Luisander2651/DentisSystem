<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Domain\Service\LogoutService;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;

final readonly class LogoutUseCase
{
    public function __construct(
        private LogoutService $logoutService,
    ) {}

    public function execute(UserModel|PatientModel|null $actor, ?string $plainTextToken = null): void
    {
        if ($actor === null) {
            throw AuthAplicationExceptions::unauthenticatedContext();
        }

        $this->logoutService->logout($actor, $plainTextToken);
    }
}
