<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Http\Requests;

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 019 (CA11, TM6): the month of a report, AAAA-MM, from January 2020 to the month in course.
 * The range is ReportMonth's, so the request and the use case refuse the same months (D18).
 */
final class MonthlyReportRequest extends FormRequest
{
    public const MESSAGE = 'Elige un mes entre enero de 2020 y el mes en curso.';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'month' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{4}-\d{2}\z/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        ReportMonth::create((string) $value, now());
                    } catch (ReportMonthException) {
                        $fail(self::MESSAGE);
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => self::MESSAGE,
            'month.string' => self::MESSAGE,
            'month.regex' => self::MESSAGE,
        ];
    }

    public function month(): string
    {
        return (string) $this->query('month');
    }
}
