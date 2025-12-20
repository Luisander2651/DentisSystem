<?php
namespace App\Domain\User;

use DateTimeImmutable;

final class UserEntity
{
    public function __construct(
        public readonly UserId $id,
        public UserEmail $email,
        public PasswordHash $password,
        public UserRole $role,
        public UserStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
