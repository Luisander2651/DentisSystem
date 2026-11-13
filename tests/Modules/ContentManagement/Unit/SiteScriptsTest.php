<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/*
 * Spec 026: the scripts of the public site (resources/js/site/), run in Node against a minimal
 * fake DOM by render-site-scripts.mjs. Node runs in UTC on purpose: the clinic's status must
 * follow the clock of Mexico City, not the visitor's (CA7, CA9, CA10).
 */

const SITE_TEST_SCHEDULE = [
    1 => ['09:00', '19:00'], 2 => ['09:00', '19:00'], 3 => ['09:00', '19:00'],
    4 => ['09:00', '19:00'], 5 => ['09:00', '19:00'], 6 => ['09:00', '14:00'],
];

/**
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function runSiteScript(string $scenario, array $arguments = []): array
{
    $process = new Process(
        ['node', __DIR__.'/render-site-scripts.mjs', $scenario, json_encode($arguments, JSON_THROW_ON_ERROR)],
        env: ['TZ' => 'UTC'],
    );
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, mixed>
 */
function clinicAt(string $instant): array
{
    return runSiteScript('clinic', ['schedule' => SITE_TEST_SCHEDULE, 'at' => $instant]);
}

it('says whether the clinic is open on the clinic\'s clock, whatever the visitor\'s zone', function (string $instant, bool $open) {
    expect(clinicAt($instant)['open'])->toBe($open);
})->with([
    'Monday 18:59 in Mexico City' => ['2026-11-16T18:59:00-06:00', true],
    'Monday 19:00 in Mexico City' => ['2026-11-16T19:00:00-06:00', false],
    'Saturday 13:59' => ['2026-11-21T13:59:00-06:00', true],
    'Saturday 14:00' => ['2026-11-21T14:00:00-06:00', false],
    'Sunday noon' => ['2026-11-22T12:00:00-06:00', false],
    'Monday 8:59, before opening' => ['2026-11-16T08:59:00-06:00', false],
    'Tuesday 00:30 UTC is still Monday 18:30 in the clinic' => ['2026-11-17T00:30:00+00:00', true],
    'Tuesday 01:00 UTC is Monday 19:00 in the clinic' => ['2026-11-17T01:00:00+00:00', false],
]);

it('tells the visitor when the clinic opens or closes', function () {
    expect(clinicAt('2026-11-16T18:00:00-06:00'))->toMatchArray(['near' => 'Cierra en 1 h 0 min', 'sub' => 'Cerramos a las 7:00 p. m.'])
        ->and(clinicAt('2026-11-16T08:00:00-06:00')['sub'])->toBe('Abrimos hoy a las 9:00 a. m.')
        ->and(clinicAt('2026-11-21T15:00:00-06:00')['sub'])->toBe('Abrimos el lunes a las 9:00 a. m.')
        ->and(clinicAt('2026-11-16T20:00:00-06:00')['sub'])->toBe('Abrimos mañana a las 9:00 a. m.')
        ->and(clinicAt('2026-11-22T12:00:00-06:00')['todayHours'])->toBe('Cerrado');
});

it('marks that JavaScript runs and keeps the motion when the visitor did not ask for less', function () {
    $result = runSiteScript('core', ['schedule' => SITE_TEST_SCHEDULE, 'reducedMotion' => false]);

    expect($result['rootClasses'])->toBe(['js'])
        ->and($result['reducedMotion'])->toBeFalse()
        ->and($result['api'])->toContain('clinic', 'ready', 'reducedMotion', 'ref', 'setIf')
        ->and($result['todayHours'])->toBe('9:00 a. m. – 7:00 p. m.');
});

it('stops the scripted motion when the visitor asked for less motion', function () {
    $result = runSiteScript('core', ['schedule' => SITE_TEST_SCHEDULE, 'reducedMotion' => true]);

    expect($result['rootClasses'])->toBe(['js', 'still'])
        ->and($result['reducedMotion'])->toBeTrue();
});

it('closes the folding menu with Escape and gives the focus back to its button', function () {
    expect(runSiteScript('escape', ['open' => true, 'key' => 'Escape']))->toBe(['handled' => true, 'open' => false, 'focused' => true, 'expanded' => 'false'])
        ->and(runSiteScript('escape', ['open' => false, 'key' => 'Escape']))->toBe(['handled' => false, 'open' => false, 'focused' => false, 'expanded' => null])
        ->and(runSiteScript('escape', ['open' => true, 'key' => 'Enter']))->toBe(['handled' => false, 'open' => true, 'focused' => false, 'expanded' => 'true']);
});

it('counts what is left of a promotion on the clinic\'s calendar, whatever the visitor\'s zone', function (string $at, string $left) {
    expect(runSiteScript('promotion', ['start' => '2026-11-01', 'end' => '2026-11-12', 'at' => $at])['left'])->toBe($left);
})->with([
    'ends today, noon in the clinic' => ['2026-11-12T12:00:00-06:00', 'Termina hoy'],
    'ends today, 23:30 in the clinic (already the 13th in UTC+1)' => ['2026-11-13T06:30:00+01:00', 'Termina hoy'],
    'ends tomorrow' => ['2026-11-11T12:00:00-06:00', 'Termina mañana'],
    'ends tomorrow, 23:30 in the clinic (already the 12th in UTC+1)' => ['2026-11-12T06:30:00+01:00', 'Termina mañana'],
    'ends in five days' => ['2026-11-07T12:00:00-06:00', 'Quedan 5 días'],
]);

it('fills the bar of a promotion with what is left, never below 4 %', function () {
    expect(runSiteScript('promotion', ['start' => '2026-11-01', 'end' => '2026-11-11', 'at' => '2026-11-06T12:00:00-06:00'])['bar'])->toBe(50)
        ->and(runSiteScript('promotion', ['start' => '2026-11-01', 'end' => '2026-11-11', 'at' => '2026-11-11T12:00:00-06:00'])['bar'])->toBe(4);
});

it('shows the promotions in the order of the server and removes none, with the visitor in another zone (A16)', function () {
    $result = runSiteScript('promos', [
        'at' => '2026-11-12T06:30:00+01:00',
        'tickets' => [
            ['pct' => '20', 'title' => 'Vence el 20', 'desc' => 'Algo', 'from' => '01 nov', 'to' => '20 nov', 'start' => '2026-11-01', 'end' => '2026-11-20'],
            ['pct' => '10', 'title' => 'Vence el 12', 'desc' => '', 'from' => '01 nov', 'to' => '12 nov', 'start' => '2026-11-01', 'end' => '2026-11-12'],
            ['pct' => '30', 'title' => 'Vence el 15', 'desc' => 'Algo', 'from' => '01 nov', 'to' => '15 nov', 'start' => '2026-11-01', 'end' => '2026-11-15'],
        ],
    ]);

    expect($result['title'])->toBe('Vence el 20')
        ->and($result['left'])->toBe('Quedan 9 días')
        ->and($result['of'])->toBe('1 de 3 activas')
        ->and(array_column($result['tickets'], 'hidden'))->toBe([true, false, false])
        ->and(array_column($result['tickets'], 'left'))->toBe(['Quedan 9 días', 'Termina mañana', 'Quedan 4 días']);
});

it('advances the testimonials on their own unless paused or held by the focus, the pointer or the screen', function () {
    $seen = runSiteScript('carousel', ['count' => 3, 'actions' => [
        ['tick', null], ['hold', 'focus'], ['tick', null], ['release', 'focus'], ['tick', null],
        ['togglePause', null], ['tick', null], ['next', null], ['togglePause', null], ['tick', null],
    ]]);

    expect(array_column($seen, 'current'))->toBe([1, 1, 1, 1, 2, 2, 2, 0, 0, 1])
        ->and(array_column($seen, 'moving'))->toBe([true, false, false, true, true, false, false, false, true, true]);
});

it('never advances the testimonials on its own when the visitor asked for less motion, only with the buttons', function () {
    $seen = runSiteScript('carousel', ['count' => 3, 'still' => true, 'actions' => [
        ['tick', null], ['release', 'focus'], ['tick', null], ['togglePause', null], ['togglePause', null], ['tick', null], ['next', null],
    ]]);

    expect(array_column($seen, 'current'))->toBe([0, 0, 0, 0, 0, 0, 1])
        ->and(array_column($seen, 'moving'))->toBe([false, false, false, false, false, false, false]);
});

it('keeps the focus inside the certificate viewer, wrapping at both ends', function () {
    $controls = ['cerrar', 'enlace'];

    expect(runSiteScript('trap', ['focusables' => $controls, 'current' => 'enlace', 'backwards' => false])['next'])->toBe('cerrar')
        ->and(runSiteScript('trap', ['focusables' => $controls, 'current' => 'cerrar', 'backwards' => true])['next'])->toBe('enlace')
        ->and(runSiteScript('trap', ['focusables' => $controls, 'current' => 'fuera', 'backwards' => false])['next'])->toBe('cerrar');
});
