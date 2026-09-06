<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentDateException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any valid Y-m-d date (property)', function () {
    $this->forAll(
        Eris\Generator\choose(1970, 2100),
        Eris\Generator\choose(1, 12),
        Eris\Generator\choose(1, 28), // 28 evita problemas de fin de mes/bisiestos
    )->then(function (int $year, int $month, int $day) {
        $value = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $vo = new AppointmentDate($value);

        expect($vo->value)->toBe($value);
    });
});

it('rejects strings that are not valid Y-m-d dates (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '2026/09/01',
        '01-09-2026',
        '2026-13-01',
        '2026-02-30',
        'not-a-date',
        '',
        '2026-9-1',
    ]))->then(function (string $invalid) {
        expect(fn () => new AppointmentDate($invalid))
            ->toThrow(AppointmentDateException::class);
    });
});

it('accepts a known valid date (example)', function () {
    $vo = AppointmentDate::fromString('2026-09-01');

    expect($vo->value)->toBe('2026-09-01');
});

it('rejects an invalid format (example)', function () {
    expect(fn () => AppointmentDate::fromString('09/01/2026'))
        ->toThrow(AppointmentDateException::class);
});

it('two dates with the same value are equal', function () {
    $a = new AppointmentDate('2026-09-01');
    $b = new AppointmentDate('2026-09-01');

    expect($a->equals($b))->toBeTrue();
});
