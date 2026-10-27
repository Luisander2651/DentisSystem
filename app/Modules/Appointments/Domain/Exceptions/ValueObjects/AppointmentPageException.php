<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class AppointmentPageException extends ValueObjectsException
{
    public static function tooManyItems(int $count, int $limit): self
    {
        return new self(sprintf('A page carries at most %d appointments, %d given.', $limit, $count));
    }

    public static function totalBelowItems(int $total, int $count): self
    {
        return new self(sprintf('The total <%d> is less than the %d appointments of the page.', $total, $count));
    }
}
