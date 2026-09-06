<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentStatusException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use Tests\Support\UsesEris;

uses(UsesEris::class);

it('accepts only the 4 whitelisted statuses (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'asignada', 'completada', 'cancelada', 'reprogramada',
    ]))->then(function (string $valid) {
        expect((new AppointmentStatus($valid))->value)->toBe($valid);
    });
});

it('rejects any string outside the whitelist (property)', function () {
    $this->forAll(Eris\Generator\elements([
        'ASIGNADA', 'pendiente', 'done', '', 'asignada ', ' cancelada', 'null',
    ]))->then(function (string $invalid) {
        expect(fn () => new AppointmentStatus($invalid))
            ->toThrow(AppointmentStatusException::class);
    });
});

it('factory methods build the expected status (example)', function () {
    expect(AppointmentStatus::assigned()->value)->toBe('asignada');
    expect(AppointmentStatus::completed()->value)->toBe('completada');
    expect(AppointmentStatus::cancelled()->value)->toBe('cancelada');
    expect(AppointmentStatus::rescheduled()->value)->toBe('reprogramada');
});

it('equals compares by value, not identity', function () {
    $a = AppointmentStatus::rescheduled();
    $b = AppointmentStatus::rescheduled();

    expect($a)->not->toBe($b); // distintas instancias
    expect($a->equals($b))->toBeTrue(); // pero iguales por valor
});
