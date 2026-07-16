<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions;

use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use Exception;

final class TreatmentException extends Exception
{
    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof TreatmentId) {
            return new self("Treatment with ID {$identifier->value} not found.");
        }

        return new self('Treatment not found.');
    }
}