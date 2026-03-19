<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Aplication\DTOs\GetUsersByStatusAndRoleDTO;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class GetUsersByRoleAndStatusUseCase
{
    public function __construct(
        private UserService $userService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return UserEntity[]
     */
    public function execute(GetUsersByStatusAndRoleDTO $dto): array
    {
        $this->authorization->assertCan('users.view');

        $role = null;
        $status = null;

        if ($dto->role !== null) {
            $role = UserRoleId::fromString($dto->role);
        }
        
        if ($dto->status !== null) {
             $status = UserStatus::fromString($dto->status);
        }

        return $this->userService->findByRoleAndStatus($status, $role);
    }
}