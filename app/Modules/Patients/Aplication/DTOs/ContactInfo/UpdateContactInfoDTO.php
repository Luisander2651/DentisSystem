<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\ContactInfo;

final readonly class UpdateContactInfoDTO
{
    private function __construct(
        public ?string $phoneNumber,
        public ?string $contactEmail,
        public ?string $emergencyContact,
    ) {}

    public static function create(
        ?string $phoneNumber = null,
        ?string $contactEmail = null,
        ?string $emergencyContact = null,
    ): self {
        return new self(
            phoneNumber: ($phoneNumber === '') ? null : $phoneNumber,
            contactEmail: ($contactEmail === '') ? null : $contactEmail,
            emergencyContact: ($emergencyContact === '') ? null : $emergencyContact,
        );
    }

    public function hasValue(): bool
    {
        return $this->phoneNumber !== null ||
            $this->contactEmail !== null ||
            $this->emergencyContact !== null;
    }
}
