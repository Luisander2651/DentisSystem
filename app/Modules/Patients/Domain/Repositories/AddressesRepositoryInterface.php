<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\Entities\Address;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

interface AddressesRepositoryInterface
{
	public function save(Address $address): void;

	public function findByPatientId(PatientId $patientId): ?Address;

	public function deleteByPatientId(PatientId $patientId): void;
}