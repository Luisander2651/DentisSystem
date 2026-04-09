<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\Addresses;

final readonly class UpdateAddressDTO
{
    private function __construct(
        public ?string $street,
        public ?string $city,
        public ?string $state,
        public ?string $postalCode,
    ) {}

    public static function create(
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
    ): self {
        return new self(
            street: ($street === '') ? null : $street,
            city: ($city === '') ? null : $city,
            state: ($state === '') ? null : $state,
            postalCode: ($postalCode === '') ? null : $postalCode,
        );
    }

    public function hasValue(): bool
    {
        return $this->street !== null ||
            $this->city !== null ||
            $this->state !== null ||
            $this->postalCode !== null;
    }
}
