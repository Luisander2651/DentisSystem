<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\Entities\UserEntity;

final readonly class GetUsersByRoleUseCase
{
    public function __construct(
        private UserService $userService,
    ) {}

    /**
     * @return UserEntity[]
     */
    public function execute(string $role): array
    {

        $role = UserRoleId::fromString($role);

        return $this->userService->findByRole($role);
    }
}