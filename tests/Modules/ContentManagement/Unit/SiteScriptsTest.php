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
