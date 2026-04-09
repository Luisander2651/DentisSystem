<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\ContactInfoIdException;

final readonly class ContactInfoId
{
	private function __construct(
		public int $value,
	) {}

	public static function fromInt(int $id): self
	{
		if ($id <= 0) {
			throw ContactInfoIdException::mustBeGreaterThanZero($id);
		}

		return new self($id);
	}
}

