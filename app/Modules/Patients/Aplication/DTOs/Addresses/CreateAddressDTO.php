<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\Addresses;

final readonly class CreateAddressDTO
{
    private function __construct(
        public string $patientId,
        public ?string $street,
        public ?string $city,
        public ?string $state,
        public ?string $postalCode,
    ) {}

    public static function create(
        string $patientId,
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
    ): self {
        return new self(
            patientId: $patientId,
            street: ($street === '') ? null : $street,
            city: ($city === '') ? null : $city,
            state: ($state === '') ? null : $state,
            postalCode: ($postalCode === '') ? null : $postalCode,
        );
    }
}
