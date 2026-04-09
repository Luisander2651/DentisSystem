<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Entities;

use App\Modules\Patients\Domain\ValueObjects\MedicalData\Allergies;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\BloodType;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\LastDentistVisit;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\MedicalDataId;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\MedicalDataPatientId;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Medications;
use DateTimeImmutable;
use Dflydev\DotAccessData\Data;

final class MedicalData
{
	private function __construct(
		private readonly ?MedicalDataId $id,
		private readonly MedicalDataPatientId $patientId,
		private BloodType $bloodType,
		private Allergies $allergies,
		private Medications $medications,
		private LastDentistVisit $lastDentistVisit,
		private readonly DateTimeImmutable $createdAt,
		private DateTimeImmutable $updatedAt
	) {}

	public static function create(
		MedicalDataPatientId $patientId,
		BloodType $bloodType,
		Allergies $allergies,
		Medications $medications,
		LastDentistVisit $lastDentistVisit
	) {
		return new self(
			null,
			$patientId,
			$bloodType,
			$allergies,
			$medications,
			$lastDentistVisit,
			new DateTimeImmutable(),
            new DateTimeImmutable()
		);
	}

	public static function fromPrimitives(
		int $id,
		string $patientId,
		?string $bloodType,
		?array $allergies,
		?array $medications,
		?array $lastDentistVisit,
		string $createdAt,
        string $updatedAt
	): self {
		return new self(
			MedicalDataId::fromInt($id),
			new MedicalDataPatientId($patientId),
			BloodType::fromNullable($bloodType),
			Allergies::fromNullableArray($allergies),
			Medications::fromNullableArray($medications),
			LastDentistVisit::fromNullableArray($lastDentistVisit),
			new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
		);
	}

	public function update (
		?BloodType $bloodType = null,
		?Allergies $allergies = null,
		?Medications $medications = null,
		?LastDentistVisit $lastDentistVisit = null
	): void {
		if ($bloodType !== null) {
			$this->bloodType = $bloodType;
		}
		if ($allergies !== null) {
			$this->allergies = $allergies;
		}
		if ($medications !== null) {
			$this->medications = $medications;
		}
		if ($lastDentistVisit !== null) {
			$this->lastDentistVisit = $lastDentistVisit;
		}

		$this->updatedAt = new DateTimeImmutable();
	}

	public function Id(): ?MedicalDataId
	{
		return $this->id;
	}

	public function PatientId(): MedicalDataPatientId
	{
		return $this->patientId;
	}

	public function BloodType(): BloodType
	{
		return $this->bloodType;
	}

	public function Allergies(): Allergies
	{
		return $this->allergies;
	}

	public function Medications(): Medications
	{
		return $this->medications;
	}

	public function LastDentistVisit(): LastDentistVisit
	{
		return $this->lastDentistVisit;
	}

	    public function CreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function UpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
