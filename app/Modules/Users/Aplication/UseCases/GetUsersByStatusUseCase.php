<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

final readonly class GetUsersByStatusUseCase
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(string $status): array
    {
        $userStatus = UserStatus::fromString($status);

        return $this->userService->findByStatus($userStatus);
    }
}