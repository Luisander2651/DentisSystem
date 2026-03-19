<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Aplication\DTOs\SaveUserDTO;
use App\Modules\Users\Domain\Entities\UserEntity;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;

final readonly class SaveUserUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private UserService $userService,
    ) {}

    public function execute(SaveUserDTO $dto): void
    {
        $this->authorization->assertCan('users.create');

        $user = UserEntity::create(
            name: UserName::create($dto->firstName, $dto->lastName),
            email: new UserEmail($dto->email),
            password: PasswordHash::createFromPlainText($dto->password),
            roleId: new UserRoleId($dto->roleId),
        );

        $this->userService->saveUser($user);
    }
}
