<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class AppointmentStatusException extends ValueObjectsException
{
    /**
     * @param array<int, string> $validStatuses
     */
    public static function invalidStatus(string $value, array $validStatuses): self
    {
        return new self(sprintf('Invalid appointment status <%s>. Valid values: %s', $value, implode(', ', $validStatuses)));
    }
}
