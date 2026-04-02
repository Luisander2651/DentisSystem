<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Aplication\DTOs\UpdateUserDto;
use \App\Modules\Users\Domain\ValueObjects\UserId;
use \App\Modules\Users\Domain\ValueObjects\PasswordHash;
use \App\Modules\Users\Aplication\Exceptions\UserAplicationExceptions;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    public function execute(string $id, UpdateUserDto $dto): void
    {
        $this->authorization->assertCan('users.update');

        $hasvalues = $dto->hasValue();

        if (!$hasvalues) {
            throw UserAplicationExceptions::NoInfoRetrivered();
        }

        $user = $this->userService->findById(new UserId($id));

        $user->update(
            firstName: $dto->firstName,
            lastName:  $dto->lastName,
            roleId:    $dto->roleId,
            status:    $dto->status,
        );

        if ($dto->newPassword !== null) {
            $user->changePassword(PasswordHash::createFromPlainText($dto->newPassword));
        }

        $this->userService->updateUser($user);
    }
}
