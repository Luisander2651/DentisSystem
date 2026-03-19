<?php

declare(strict_types=1);

namespace App\Core\Authorization;

interface AuthorizationServiceInterface
{
    public function assertCan(string $permission): void;
}
