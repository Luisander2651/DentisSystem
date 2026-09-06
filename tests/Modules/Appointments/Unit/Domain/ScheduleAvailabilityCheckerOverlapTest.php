<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Service\ScheduleAvailabilityChecker;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/**
 * Oráculo independiente: dos intervalos [start, start+duration) se solapan
 * si y solo si ninguno de los dos "cabe" enteramente antes de que empiece el otro.
 * Implementado de forma deliberadamente distinta a ScheduleAvailabilityChecker::overlaps()
 * para no reproducir un posible bug de la implementación bajo prueba.
 */
function independentOverlapOracle(int $startA, int $durationA, int $startB, int $durationB): bool
{
    $endA = $startA + $durationA;
    $endB = $startB + $durationB;

    $aEntirelyBeforeB = $endA <= $startB;
    $bEntirelyBeforeA = $endB <= $startA;

    return ! ($aEntirelyBeforeB || $bEntirelyBeforeA);
}

it('matches an independent overlap oracle for random intervals (property)', function () {
    $this->forAll(
        Eris\Generator\choose(0, 1439),  // inicio A, en minutos desde medianoche
        Eris\Generator\choose(1, 240),   // duracion A
        Eris\Generator\choose(0, 1439),  // inicio B
        Eris\Generator\choose(1, 240),   // duracion B
    )->then(function (int $startA, int $durationA, int $startB, int $durationB) {
        $endA = $startA + $durationA;
        $endB = $startB + $durationB;

        $actual = ScheduleAvailabilityChecker::overlaps($startA, $endA, $startB, $endB);
        $expected = independentOverlapOracle($startA, $durationA, $startB, $durationB);

        expect($actual)->toBe($expected);
    });
});

it('an interval never overlaps a copy of itself shifted past its own end (property)', function () {
    $this->forAll(
        Eris\Generator\choose(0, 1439),
        Eris\Generator\choose(1, 240),
    )->then(function (int $start, int $duration) {
        $end = $start + $duration;

        expect(ScheduleAvailabilityChecker::overlaps($start, $end, $end, $end + 30))->toBeFalse();
    });
});

it('detects an exact identical overlap (example)', function () {
    expect(ScheduleAvailabilityChecker::overlaps(600, 630, 600, 630))->toBeTrue();
});

it('detects a partial overlap (example)', function () {
    // A: 10:00-10:30 (600-630), B: 10:15-10:45 (615-645)
    expect(ScheduleAvailabilityChecker::overlaps(600, 630, 615, 645))->toBeTrue();
});

it('does not detect overlap for back-to-back appointments (example)', function () {
    // A termina exactamente cuando B empieza -> no se solapan (intervalo semi-abierto)
    expect(ScheduleAvailabilityChecker::overlaps(600, 630, 630, 660))->toBeFalse();
});

it('does not detect overlap for clearly separate appointments (example)', function () {
    expect(ScheduleAvailabilityChecker::overlaps(600, 630, 900, 930))->toBeFalse();
});
