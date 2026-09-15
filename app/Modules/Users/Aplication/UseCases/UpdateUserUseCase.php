<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Aplication\DTOs\UpdateUserDto;
use App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    public function execute(string $id, UpdateUserDto $dto): void
    {
        $this->authorization->assertCan('users.update');

        // BR-22 moved this check into UpdateUserRequest, where an empty payload is a 422
        // rather than the 409 it used to be. The guard stays as a backstop for callers
        // that reach the use case without going through the HTTP layer.
        if (! $dto->hasValue()) {
            throw UserAplicationExceptions::NoInfoRetrivered();
        }

        $userId = new UserId($id);

        // BR-21: an administrator may change their own name and password, but not their
        // own role or status - those are the two fields that would cost them their access.
        if ((string) (Auth::user()?->getAuthIdentifier() ?? '') === $userId->value) {
            if ($dto->roleId !== null) {
                throw AuthorizationException::selfLockout('change the role of');
            }

            if ($dto->status !== null) {
                throw AuthorizationException::selfLockout('change the status of');
            }
        }

        $user = $this->userService->findById($userId);

        $user->update(
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            roleId: $dto->roleId,
            status: $dto->status,
        );

        if ($dto->newPassword !== null) {
            $user->changePassword(PasswordHash::createFromPlainText($dto->newPassword));
        }

        $this->userService->updateUser($user);
    }
}
