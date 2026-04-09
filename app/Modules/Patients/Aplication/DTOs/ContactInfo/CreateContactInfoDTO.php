<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\ContactInfo;

final readonly class CreateContactInfoDTO
{
    private function __construct(
        public string $patientId,
        public ?string $phoneNumber,
        public ?string $contactEmail,
        public ?string $emergencyContact,
    ) {}

    public static function create(
        string $patientId,
        ?string $phoneNumber = null,
        ?string $contactEmail = null,
        ?string $emergencyContact = null,
    ): self {
        return new self(
            patientId: $patientId,
            phoneNumber: ($phoneNumber === '') ? null : $phoneNumber,
            contactEmail: ($contactEmail === '') ? null : $contactEmail,
            emergencyContact: ($emergencyContact === '') ? null : $emergencyContact,
        );
    }
}
