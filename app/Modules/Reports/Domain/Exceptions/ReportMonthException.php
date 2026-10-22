<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Exceptions;

use Exception;

/**
 * Spec 019 (CA11, TM6): a month that is not one of the range of the reports. Its message is the
 * one the API answers in its 422, so the FormRequest and the use case say the same.
 */
final class ReportMonthException extends Exception
{
    public static function outOfRange(): self
    {
        return new self('Elige un mes entre enero de 2020 y el mes en curso.');
    }
}
