<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Service;

use App\Modules\Patients\Domain\Repositories\AddressesRepositoryInterface;
use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class AdressesService
{
    private readonly AddressesRepositoryInterface $addressesRepository;

    public function __construct(AddressesRepositoryInterface $addressesRepository)
    {
        $this->addressesRepository = $addressesRepository;
    }

    public function saveOrUpdateAddress(Address $address): void
    {
        $this->addressesRepository->save($address);
    }

    public function deleteAddress(PatientId $patientId): void
    {
        $this->addressesRepository->deleteByPatientId($patientId);
    }
}