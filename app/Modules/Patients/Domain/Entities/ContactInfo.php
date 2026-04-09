<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Entities;

use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactEmail;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactInfoId;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\ContactInfoPatientId;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\EmergencyContact;
use App\Modules\Patients\Domain\ValueObjects\ContactInfo\PhoneNumber;
use DateTimeImmutable;

final class ContactInfo
{
	private function __construct(
		private readonly ?ContactInfoId $id,
		private readonly ContactInfoPatientId $patientId,
		private PhoneNumber $phoneNumber,
		private ContactEmail $contactEmail,
		private EmergencyContact $emergencyContact,
		private readonly DateTimeImmutable $createdAt,
		private DateTimeImmutable $updatedAt,
	) {}

	public static function create(
		ContactInfoPatientId $patientId,
		PhoneNumber $phoneNumber,
		ContactEmail $contactEmail,
		EmergencyContact $emergencyContact,
	): self {
		return new self(
			null,
			$patientId,
			$phoneNumber,
			$contactEmail,
			$emergencyContact,
			new DateTimeImmutable(),
			new DateTimeImmutable(),
		);
	}

	public static function fromPrimitives(
		int $id,
		string $patientId,
		?string $phoneNumber,
		?string $contactEmail,
		?string $emergencyContact,
		string $createdAt,
		string $updatedAt,
	): self {
		return new self(
			ContactInfoId::fromInt($id),
			new ContactInfoPatientId($patientId),
			PhoneNumber::fromNullable($phoneNumber),
			ContactEmail::fromNullable($contactEmail),
			EmergencyContact::fromNullable($emergencyContact),
			new DateTimeImmutable($createdAt),
			new DateTimeImmutable($updatedAt),
		);
	}

	public function update(
		?PhoneNumber $phoneNumber = null,
		?ContactEmail $contactEmail = null,
		?EmergencyContact $emergencyContact = null,
	): void {
		if ($phoneNumber !== null) {
			$this->phoneNumber = $phoneNumber;
		}

		if ($contactEmail !== null) {
			$this->contactEmail = $contactEmail;
		}

		if ($emergencyContact !== null) {
			$this->emergencyContact = $emergencyContact;
		}

		$this->updatedAt = new DateTimeImmutable();
	}

	public function Id(): ?ContactInfoId
	{
		return $this->id;
	}

	public function PatientId(): ContactInfoPatientId
	{
		return $this->patientId;
	}

	public function PhoneNumber(): PhoneNumber
	{
		return $this->phoneNumber;
	}

	public function ContactEmail(): ContactEmail
	{
		return $this->contactEmail;
	}

	public function EmergencyContact(): EmergencyContact
	{
		return $this->emergencyContact;
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
