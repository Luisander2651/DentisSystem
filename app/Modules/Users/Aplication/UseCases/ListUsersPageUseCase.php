<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Users\Aplication\DTOs\ListUsersPageDTO;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

/**
 * Spec 028 (CA5, CA14; P5): one page of the staff with the counts by role of the whole list, which
 * ignore the filters.
 */
final readonly class ListUsersPageUseCase
{
    public function __construct(
        private UserService $userService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<UserEntity>, counts: array{total: int, administrador: int, asistente: int, doctor: int}}
     */
    public function execute(ListUsersPageDTO $dto): array
    {
        $this->authorization->assertCan('users.view');

        $status = $dto->status === null ? null : UserStatus::fromString($dto->status);
        $role = $dto->role === null ? null : UserRoleId::fromString($dto->role);

        return [
            'page' => $this->userService->findPage(PageRequest::create($dto->page, $dto->perPage), $status, $role),
            'counts' => $this->userService->countByRole(),
        ];
    }
}
