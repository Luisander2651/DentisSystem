<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Auth;

final readonly class DeleteUserByIdUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    public function execute(string $id): void
    {
        $this->authorization->assertCan('users.delete');

        $userId = new UserId($id);

        // BR-21: deleting your own account is the fastest way to lock the clinic out of
        // its own admin panel, and the deletion is hard - there is no deactivated row to
        // restore afterwards.
        if ((string) (Auth::user()?->getAuthIdentifier() ?? '') === $userId->value) {
            throw AuthorizationException::selfLockout('delete');
        }

        $this->userService->deleteById($userId);
    }
}
