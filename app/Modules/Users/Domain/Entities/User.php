<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Core\Domain\UuidIdentifier;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRole;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

use DateTimeImmutable;

final class UserEntity
{
    private function __construct(
        public readonly UserId $id,
        public UserEmail $email,
        public PasswordHash $password,
        public UserRole $role,
        public UserStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        UserId $id,
        UserEmail $email,
        PasswordHash $password,
        UserRole $role,
        UserStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            email: $email,
            password: $password,
            role: $role,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }
}