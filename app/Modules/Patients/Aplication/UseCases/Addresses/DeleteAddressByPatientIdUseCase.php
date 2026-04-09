<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\Addresses;

use App\Modules\Patients\Aplication\DTOs\Addresses\DeleteAddressDTO;
use App\Modules\Patients\Aplication\Exceptions\Addresses\AddressAplicationExceptions;
use App\Modules\Patients\Domain\Service\AdressesService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class DeleteAddressByPatientIdUseCase
{
    public function __construct(
        private AdressesService $addressesService,
    ) {}

    public function execute(DeleteAddressDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw AddressAplicationExceptions::IdNotProvided();
        }

        $this->addressesService->deleteAddress(new PatientId($dto->patientId));
    }
}
