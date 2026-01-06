<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Core\Domain\UuidIdentifier;
use App\Modules\Users\Domain\ValueObjects\PasswordHash;
use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Domain\ValueObjects\UserStatus;
use App\Modules\Users\Domain\ValueObjects\UserName;

use DateTimeImmutable;

final class UserEntity
{
    private function __construct(
        public readonly UserId $id,
        public UserName $name,
        public readonly UserEmail $email,
        public PasswordHash $password,
        public readonly UserRoleId $role,
        public UserStatus $status,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        UserName $name,
        UserEmail $email,
        PasswordHash $password,
        UserRoleId $roleId
    ): self {
        return new self(
            UserId::random(),
            $name,
            $email,
            $password,
            $roleId,
            UserStatus::active(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $roleId,
        string $status,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new UserId($id),
            UserName::create($firstName, $lastName),
            new UserEmail($email),
            PasswordHash::fromString($password),
            UserRoleId::fromDatabaseId($roleId),
            UserStatus::fromString($status),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function activate(): void
    {
        if ($this->status->isActive()) {
            return;
        }
        $this->status = UserStatus::active();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if ($this->status->isInactive()) {
            return;
        }
        $this->status = UserStatus::inactive();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isPasswordValid(string $plainPassword): bool
    {
        return $this->password->verify($plainPassword);
    }

    public function changePassword(PasswordHash $newPassword): void
    {
        $this->password = $newPassword;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeName(UserName $newName): void
    {
        $this->name = $newName;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters para que el Repositorio pueda leer los datos al guardar
    public function id(): UserId
    {
        return $this->id;
    }
    public function name(): UserName
    {
        return $this->name;
    }
    public function email(): UserEmail
    {
        return $this->email;
    }
    public function password(): PasswordHash
    {
        return $this->password;
    }
    public function role(): UserRoleId
    {
        return $this->role;
    }
    public function status(): UserStatus
    {
        return $this->status;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
