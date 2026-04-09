<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\Addresses;

use App\Modules\Patients\Aplication\DTOs\Addresses\CreateAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\Service\AdressesService;
use App\Modules\Patients\Domain\ValueObjects\Addresses\AddressPatientId;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;

final readonly class SaveAddressUseCase
{
    public function __construct(
        private AddressesRepositoryInterface $addressesRepository,
        private AdressesService $addressesService,
    ) {}

    public function execute(CreateAddressDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw AddressAplicationExceptions::IdNotProvided();
        }

        $existingAddress = $this->addressesRepository->findByPatientId(new PatientId($dto->patientId));

        if ($existingAddress !== null) {
            throw AddressAplicationExceptions::AlreadyExists();
        }

        $address = Address::create(
            patientId: new AddressPatientId($dto->patientId),
            street: Street::fromNullable($dto->street),
            city: City::fromNullable($dto->city),
            state: State::fromNullable($dto->state),
            postalCode: PostalCode::fromNullable($dto->postalCode),
        );

        $this->addressesService->saveOrUpdateAddress($address);
    }
}
