<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonImmutable;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 019, CA9, CA11 and TM6: every month of the range is accepted and sits after the one
 * before it; anything else is refused.
 */

function reportPropertiesToday(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City');
}

it('accepts every month of the range, which comes after its previous one (property)', function () {
    $this->forAll(Eris\Generator\choose(2020, 2026), Eris\Generator\choose(1, 12))->then(function (int $year, int $month) {
        $value = sprintf('%04d-%02d', $year, $month);

        if ($value > '2026-10') {
            return;
        }

        $reportMonth = ReportMonth::create($value, reportPropertiesToday());

        expect($reportMonth->value())->toBe($value)
            ->and($reportMonth->previous()->value() < $value)->toBeTrue()
            ->and($reportMonth->firstDay()->lessThanOrEqualTo(reportPropertiesToday()))->toBeTrue();
    });
});

it('refuses every month after the month in course (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 500))->then(function (int $monthsAhead) {
        $value = reportPropertiesToday()->startOfMonth()->addMonths($monthsAhead)->format('Y-m');

        expect(fn () => ReportMonth::create($value, reportPropertiesToday()))->toThrow(ReportMonthException::class);
    });
});

it('refuses every month before January 2020 (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 1000))->then(function (int $monthsBefore) {
        $value = CarbonImmutable::parse('2020-01-01')->subMonths($monthsBefore)->format('Y-m');

        expect(fn () => ReportMonth::create($value, reportPropertiesToday()))->toThrow(ReportMonthException::class);
    });
});

it('refuses every text that is not a month written AAAA-MM (property)', function () {
    $this->forAll(Eris\Generator\string())->then(function (string $text) {
        if (preg_match('/^\d{4}-\d{2}$/', $text) === 1) {
            return;
        }

        expect(fn () => ReportMonth::create($text, reportPropertiesToday()))->toThrow(ReportMonthException::class);
    });
});
