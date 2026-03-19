<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\UseCases;

use App\Modules\Auth\Aplication\DTOs\LoginDTO;
use App\Modules\Auth\Domain\Service\LoginService;

final readonly class LoginUseCase
{
    public function __construct(
        private LoginService $loginService,
    ) {}

    /**
     * @return array{token:string,actorType:string,actorId:string,name:string,email:string}
     */
    public function execute(LoginDTO $dto): array
    {
        return $this->loginService->login(
            email: $dto->email,
            password: $dto->password,
        );
    }
}
