<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\Entities\ContactInfo;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

interface ContactInfoRepositoryInterface
{
	public function save(ContactInfo $contactInfo): void;

	public function findByPatientId(PatientId $patientId): ?ContactInfo;

	public function deleteByPatientId(PatientId $patientId): void;
}