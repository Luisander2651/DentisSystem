<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\Addresses;

use App\Modules\Patients\Aplication\DTOs\Addresses\UpdateAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Domain\Service\AdressesService;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class UpdateAddressUseCase
{
    public function __construct(
        private AddressesRepositoryInterface $addressesRepository,
        private AdressesService $addressesService,
    ) {}

    public function execute(string $patientId, UpdateAddressDTO $dto): void
    {
        if ($patientId === '') {
            throw AddressAplicationExceptions::IdNotProvided();
        }

        if (!$dto->hasValue()) {
            throw AddressAplicationExceptions::NoInfoProvided();
        }

        $address = $this->addressesRepository->findByPatientId(new PatientId($patientId));

        if ($address === null) {
            throw AddressAplicationExceptions::NotFound();
        }

        $address->update(
            street: $dto->street !== null ? Street::fromNullable($dto->street) : null,
            city: $dto->city !== null ? City::fromNullable($dto->city) : null,
            state: $dto->state !== null ? State::fromNullable($dto->state) : null,
            postalCode: $dto->postalCode !== null ? PostalCode::fromNullable($dto->postalCode) : null,
        );

        $this->addressesService->saveOrUpdateAddress($address);
    }
}
