<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentTimeException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts any valid H:i time (property)', function () {
    $this->forAll(
        Eris\Generator\choose(0, 23),
        Eris\Generator\choose(0, 59),
    )->then(function (int $hour, int $minute) {
        $value = sprintf('%02d:%02d', $hour, $minute);

        $vo = new AppointmentTime($value);

        expect($vo->value)->toBe($value);
    });
});

it('rejects strings that are not valid H:i or H:i:s times (property)', function () {
    $this->forAll(Eris\Generator\elements([
        '24:00',
        '10:60',
        '9:00',
        '10-00',
        'not-a-time',
        '',
        '25:99',
    ]))->then(function (string $invalid) {
        expect(fn () => new AppointmentTime($invalid))
            ->toThrow(AppointmentTimeException::class);
    });
});

it('accepts a known valid time in H:i (example)', function () {
    expect(AppointmentTime::fromString('10:00')->value)->toBe('10:00');
});

it('accepts a known valid time in H:i:s (example)', function () {
    expect(AppointmentTime::fromString('10:00:00')->value)->toBe('10:00:00');
});

it('rejects an invalid format (example)', function () {
    expect(fn () => AppointmentTime::fromString('10.00'))
        ->toThrow(AppointmentTimeException::class);
});
