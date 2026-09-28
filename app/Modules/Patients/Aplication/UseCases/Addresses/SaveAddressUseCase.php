<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\Addresses;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Aplication\DTOs\Addresses\CreateAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Domain\Service\AdressesService;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Addresses\AddressPatientId;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class SaveAddressUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AddressesRepositoryInterface $addressesRepository,
        private AdressesService $addressesService,
        private PatientService $patientService,
    ) {}

    public function execute(CreateAddressDTO $dto): void
    {
        $this->authorization->assertCan('patients.clinical-data.manage');

        if ($dto->patientId === '') {
            throw AddressAplicationExceptions::IdNotProvided();
        }

        $patientId = new PatientId($dto->patientId);
        $this->patientService->findById($patientId);

        $existingAddress = $this->addressesRepository->findByPatientId($patientId);

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
