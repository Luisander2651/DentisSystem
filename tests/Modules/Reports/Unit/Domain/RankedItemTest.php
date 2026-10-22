<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;

/*
 * Spec 019, CA5 and CA6: one line of a ranking (a treatment, or a member of the staff with the
 * appointments they completed as its extra figure).
 */

it('keeps the name, the figure and the extra figure', function () {
    $doctor = RankedItem::create('Daniela Prueba', 5, 2);
    $treatment = RankedItem::create('Limpieza dental de prueba', 3);

    expect($doctor->name)->toBe('Daniela Prueba')
        ->and($doctor->value)->toBe(5)
        ->and($doctor->extra)->toBe(2)
        ->and($doctor->isTop)->toBeFalse()
        ->and($treatment->extra)->toBeNull();
});

it('marks the line as one of the top ones without changing the rest', function () {
    $top = RankedItem::create('Daniela Prueba', 5, 2)->asTop();

    expect($top->isTop)->toBeTrue()
        ->and($top->name)->toBe('Daniela Prueba')
        ->and($top->value)->toBe(5)
        ->and($top->extra)->toBe(2);
});

it('keeps a name that would be a formula in a spreadsheet as it is', function () {
    expect(RankedItem::create('=HYPERLINK("http://x","y")', 1)->name)->toBe('=HYPERLINK("http://x","y")');
});

it('refuses an empty name', function (string $name) {
    expect(fn () => RankedItem::create($name, 1))->toThrow(MonthlyReportException::class);
})->with(['empty' => '', 'only spaces' => '   ']);

it('refuses a negative figure', function (int $value, ?int $extra) {
    expect(fn () => RankedItem::create('Daniela Prueba', $value, $extra))->toThrow(MonthlyReportException::class);
})->with(['figure' => [-1, null], 'extra figure' => [3, -1]]);
