<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\Exceptions;

use Exception;

final class AppointmentTrackingApplicationException extends Exception
{
    public static function noInfoProvided(): self
    {
        return new self('At least one field must be provided for update.');
    }

    public static function invalidPrescriptionFormat(int $index): self
    {
        return new self(sprintf('Prescription at index %d must be an array.', $index));
    }

    public static function unexpectedPrescriptionFields(int $index, array $fields): self
    {
        return new self(sprintf(
            'Prescription at index %d has unexpected fields: %s.',
            $index,
            implode(', ', $fields),
        ));
    }

    public static function missingPrescriptionFields(int $index, array $fields): self
    {
        return new self(sprintf(
            'Prescription at index %d is missing required fields: %s.',
            $index,
            implode(', ', $fields),
        ));
    }
}
