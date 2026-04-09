<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Entities;

use App\Modules\Patients\Domain\ValueObjects\Addresses\AddressId;
use App\Modules\Patients\Domain\ValueObjects\Addresses\AddressPatientId;
use App\Modules\Patients\Domain\ValueObjects\Addresses\City;
use App\Modules\Patients\Domain\ValueObjects\Addresses\PostalCode;
use App\Modules\Patients\Domain\ValueObjects\Addresses\State;
use App\Modules\Patients\Domain\ValueObjects\Addresses\Street;
use DateTimeImmutable;

final class Address
{
	private function __construct(
		private readonly ?AddressId $id,
		private readonly AddressPatientId $patientId,
		private Street $street,
		private City $city,
		private State $state,
		private PostalCode $postalCode,
		private readonly DateTimeImmutable $createdAt,
		private DateTimeImmutable $updatedAt,
	) {}

	public static function create(
		AddressPatientId $patientId,
		Street $street,
		City $city,
		State $state,
		PostalCode $postalCode,
	): self {
		return new self(
			null,
			$patientId,
			$street,
			$city,
			$state,
			$postalCode,
			new DateTimeImmutable(),
			new DateTimeImmutable(),
		);
	}

	public static function fromPrimitives(
		int $id,
		string $patientId,
		?string $street,
		?string $city,
		?string $state,
		?string $postalCode,
		string $createdAt,
		string $updatedAt,
	): self {
		return new self(
			AddressId::fromInt($id),
			new AddressPatientId($patientId),
			Street::fromNullable($street),
			City::fromNullable($city),
			State::fromNullable($state),
			PostalCode::fromNullable($postalCode),
			new DateTimeImmutable($createdAt),
			new DateTimeImmutable($updatedAt),
		);
	}

	public function update(
		?Street $street = null,
		?City $city = null,
		?State $state = null,
		?PostalCode $postalCode = null,
	): void {
		if ($street !== null) {
			$this->street = $street;
		}

		if ($city !== null) {
			$this->city = $city;
		}

		if ($state !== null) {
			$this->state = $state;
		}

		if ($postalCode !== null) {
			$this->postalCode = $postalCode;
		}

		$this->updatedAt = new DateTimeImmutable();
	}

	public function Id(): ?AddressId
	{
		return $this->id;
	}

	public function PatientId(): AddressPatientId
	{
		return $this->patientId;
	}

	public function Street(): Street
	{
		return $this->street;
	}

	public function City(): City
	{
		return $this->city;
	}

	public function State(): State
	{
		return $this->state;
	}

	public function PostalCode(): PostalCode
	{
		return $this->postalCode;
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
