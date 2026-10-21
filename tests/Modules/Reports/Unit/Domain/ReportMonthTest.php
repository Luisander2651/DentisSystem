<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/*
 * Spec 019, CA9 and CA11: the month of a report, from January 2020 to the month in course.
 */

function reportToday(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City');
}

it('accepts a month between January 2020 and the month in course', function (string $value) {
    expect(ReportMonth::create($value, reportToday())->value())->toBe($value);
})->with(['2020-01', '2026-07', '2026-10']);

it('refuses a month that is not a valid month of the range', function (string $value) {
    expect(fn () => ReportMonth::create($value, reportToday()))
        ->toThrow(ReportMonthException::class, 'Elige un mes entre enero de 2020 y el mes en curso.');
})->with([
    'month 13' => '2026-13',
    'month 00' => '2026-00',
    'month without leading zero' => '2026-7',
    'a word' => 'julio',
    'empty' => '',
    'before January 2020' => '2019-12',
    'the next month' => '2026-11',
    'with spaces' => ' 2026-07',
    'with an injection' => "2026-07' OR 1=1",
]);

it('goes back one month, across the year', function () {
    expect(ReportMonth::create('2026-01', reportToday())->previous()->value())->toBe('2025-12')
        ->and(ReportMonth::create('2026-07', reportToday())->previous()->value())->toBe('2026-06');
});

it('knows its first and its last day', function () {
    $july = ReportMonth::create('2026-07', reportToday());
    $leapFebruary = ReportMonth::create('2024-02', reportToday());

    expect($july->firstDay()->toDateString())->toBe('2026-07-01')
        ->and($july->lastDay()->toDateString())->toBe('2026-07-31')
        ->and($leapFebruary->lastDay()->toDateString())->toBe('2024-02-29');
});

it('knows whether it is the month in course', function () {
    expect(ReportMonth::create('2026-10', reportToday())->isCurrent(reportToday()))->toBeTrue()
        ->and(ReportMonth::create('2026-09', reportToday())->isCurrent(reportToday()))->toBeFalse();
});

it('names the month in Spanish', function () {
    $july = ReportMonth::create('2026-07', reportToday());

    expect($july->label())->toBe('julio de 2026')
        ->and($july->shortLabel())->toBe('julio')
        ->and($july->optionLabel())->toBe('Julio de 2026')
        ->and(ReportMonth::create('2020-01', reportToday())->label())->toBe('enero de 2020');
});

it('gives the last complete month', function () {
    expect(ReportMonth::lastComplete(reportToday())->value())->toBe('2026-09')
        ->and(ReportMonth::lastComplete(CarbonImmutable::parse('2027-01-01 00:05:00', 'America/Mexico_City'))->value())->toBe('2026-12');
});

it('lists the months to choose, from the month in course back to January 2020', function () {
    $months = ReportMonth::selectable(reportToday());

    expect($months[0]->value())->toBe('2026-10')
        ->and($months[0]->optionLabel())->toBe('Octubre de 2026')
        ->and(end($months)->value())->toBe('2020-01')
        ->and($months)->toHaveCount(82);
});

it('takes the mutable date that now() returns as today', function () {
    $today = Carbon::parse('2026-10-19 10:00:00', 'America/Mexico_City');

    expect(ReportMonth::create('2026-10', $today)->isCurrent($today))->toBeTrue()
        ->and(ReportMonth::lastComplete($today)->value())->toBe('2026-09')
        ->and(ReportMonth::selectable($today))->toHaveCount(82);
});
