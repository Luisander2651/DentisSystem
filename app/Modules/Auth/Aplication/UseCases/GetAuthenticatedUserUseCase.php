<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserId;

/**
 * The attendance microservice asks who owns an app token before it accepts a connection or a
 * command, so the answer is read from the database on every call: a deactivated administrator
 * or one whose role changed is refused even while their token is still valid.
 */
final readonly class GetAuthenticatedUserUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    public function execute(): UserEntity
    {
        $this->authorization->assertCan('auth.session.view');

        return $this->userService->findById(new UserId($this->authorization->currentActorId()));
    }
}
