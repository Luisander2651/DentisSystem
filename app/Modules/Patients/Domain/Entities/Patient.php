<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Entities;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientRole;

use DateTimeImmutable;

final class Patient
{
    private function __construct(
        private readonly PatientId $id,
        private PatientName $name,
        private readonly PatientEmail $email,
        private PasswordHash $passwordHash,
        private PatientStatus $status,
        private readonly PatientRole $role,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        PatientName $name,
        PatientEmail $email,
        PasswordHash $passwordHash,
    ): self {
        return new self(
            PatientId::random(), 
            $name, 
            $email, 
            $passwordHash, 
            PatientStatus::active(), 
            PatientRole::patient(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        string $status,
        string $role,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new PatientId($id),
            PatientName::create($firstName, $lastName),
            PatientEmail::fromString($email),
            PasswordHash::fromString($passwordHash),
            PatientStatus::fromString($status),
            PatientRole::fromString($role),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function update(
        ?string $firstName,
        ?string $lastName,
        ?string $patientStatus,
    ): void {
        if($firstName !== null || $lastName !== null) {
            $this->name = PatientName::create(
                $firstName ?? $this->name->firstName,
                $lastName ?? $this->name->lastName
            );
        }

        $this->status = $patientStatus !== null ? PatientStatus::fromString($patientStatus) : $this->status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        if ($this->status->isActive()) {
            return;
        }
        $this->status = PatientStatus::active();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if ($this->status->isInactive()) {
            return;
        }
        $this->status = PatientStatus::inactive();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function passwordMatches(string $plainPassword): bool
    {
        return $this->passwordHash->verify($plainPassword);
    }

    public function changePassword(PasswordHash $newPassword): void
    {
        $this->passwordHash = $newPassword;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeName(PatientName $newName): void
    {
        $this->name = $newName;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getter para que el reposiotorio pueda leer los datos al guardar
    public function Email(): PatientEmail
    {
        return $this->email;
    }

    public function Id(): PatientId
    {
        return $this->id;
    }

    public function Name(): PatientName
    {
        return $this->name;
    }

    public function PasswordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function Status(): PatientStatus
    {
        return $this->status;
    }
    
    public function Role(): PatientRole
    {
        return $this->role;
    }

    public function CreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function UpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}