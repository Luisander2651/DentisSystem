<?php

declare(strict_types=1);

namespace App\Modules\Users\Aplication\UseCases;

use App\Modules\Users\Domain\Service\UserService;
use App\Modules\Users\Aplication\DTOs\UpdateUserDto;
use \App\Modules\Users\Domain\ValueObjects\UserId;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(string $id, UpdateUserDto $dto): void
    {
        $hasvalues = $dto->hasValue();

        if (!$hasvalues) {
            throw new \InvalidArgumentException('At least one field must be provided for update.');
        }

        $user = $this->userService->findById(new UserId($id));

        $user->update(
            firstName: $dto->firstName,
            lastName:  $dto->lastName,
            roleId:    $dto->roleId,
            status:    $dto->status,
        );

        $this->userService->updateUser($user);
    }
}
