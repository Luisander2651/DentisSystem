<?php

namespace App\Domain\Patient\Attributes;
use DateTimeImmutable;

final class PatientEntity
{
    public function __construct(
        public readonly PatientId $id,
        public PatientName $name,
        public PatientEmail $email,
        public PatientPhone $phone,
        public PatientStatus $status,
        public RoleId $roleId,
        public MedicalDataId $medicalDataId,
        public ContactInfoId $contactInfoId,
        public AddressId $addressId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}