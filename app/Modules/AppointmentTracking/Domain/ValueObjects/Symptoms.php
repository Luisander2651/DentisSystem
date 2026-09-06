<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\SymptomsException;

final readonly class Symptoms
{
    private function __construct(
        public array $value,
    ) {}

    public static function fromArray(array $symptoms): self
    {
        foreach ($symptoms as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw SymptomsException::allValuesMustBeNonEmptyStrings();
            }
        }

        return new self(array_values($symptoms));
    }
}
