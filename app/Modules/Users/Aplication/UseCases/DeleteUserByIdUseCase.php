<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserId;

final readonly class DeleteUserByIdUseCase
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(string $id): void
    {
        $userId = new UserId($id);
        $this->userService->deleteById($userId);
    }
}