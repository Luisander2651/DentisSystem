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

/**
 * @return array<string, mixed>
 */
function tourWith(bool $webgl, bool $reducedMotion, bool $context = true): array
{
    return runSiteScript('tooth', ['webgl' => $webgl, 'context' => $context, 'reducedMotion' => $reducedMotion, 'tokens' => [
        '--color-enamel' => '#ff0000', '--color-enamel-white' => ' #00ff00', '--color-primary' => '#0000ff',
        '--color-secondary' => '#808080', '--color-enamel-rim' => '#fff', '--color-env-dark' => '#000',
        '--color-env-light' => '#eee', '--color-metal' => '#ccc', '--color-blush' => '#ddd',
        '--color-primary-deep' => '#111', '--color-ink' => '#222',
    ]]);
}

it('keeps the still tour, with no canvas, in a browser without 3D graphics (CA9)', function () {
    expect(tourWith(webgl: false, reducedMotion: false))->toMatchArray(['drawn' => false, 'classes' => ['tour-static'], 'canvases' => [], 'colours' => []]);
});

it('keeps the still tour, with no canvas, when the visitor asked for less motion (CA9)', function () {
    expect(tourWith(webgl: true, reducedMotion: true))->toMatchArray(['drawn' => false, 'classes' => ['tour-static'], 'canvases' => [], 'colours' => []]);
});

it('falls back to the still tour when the browser refuses the WebGL context', function () {
    expect(tourWith(webgl: true, reducedMotion: false, context: false))->toMatchArray(['drawn' => false, 'classes' => ['tour-static'], 'canvases' => []]);
});

it('draws the tooth with the colours of the tokens, none written in the script (A30)', function () {
    $tour = tourWith(webgl: true, reducedMotion: false);

    expect($tour)->toMatchArray(['drawn' => true, 'classes' => ['tour-live'], 'canvases' => ['pt pt-b', 'cv', 'pt pt-f']])
        ->and($tour['colours'])->toMatchArray([
            'uEnamel' => [1, 0, 0],
            'uEnamelWhite' => [0, 1, 0],
            'uPrimary' => [0, 0, 1],
            'uSecondary' => [0.502, 0.502, 0.502],
            'uEnamelRim' => [1, 1, 1],
        ]);
});

it('lets each section enter once: it keeps its place after leaving the screen and is no longer watched (M-1)', function () {
    expect(runSiteScript('reveal', ['observer' => true, 'entering' => ['a', 'c']]))->toBe(['shown' => ['a', 'c'], 'unobserved' => ['a', 'c']])
        ->and(runSiteScript('reveal', ['observer' => false, 'entering' => []]))->toBe(['shown' => ['a', 'b', 'c'], 'unobserved' => []]);
});

it('loads a photo of the gallery tour only when it comes near the view of the tour, and every photo without an observer (T019)', function () {
    expect(runSiteScript('galleryPhotos', ['observer' => true, 'entering' => ['b']]))
        ->toBe(['loaded' => ['b'], 'waiting' => ['a', 'c'], 'unobserved' => ['b'], 'rootIsTheView' => true])
        ->and(runSiteScript('galleryPhotos', ['observer' => false, 'entering' => []]))
        ->toBe(['loaded' => ['a', 'b', 'c'], 'waiting' => [], 'unobserved' => [], 'rootIsTheView' => null]);
});

it('pins the gallery tour as tall as its sideways distance and moves it with the scroll (T019)', function () {
    $tour = runSiteScript('galleryTour', ['reduced' => false, 'positions' => [92, -908, -1908, 500], 'tilts' => [720, 0, 2000]]);

    expect($tour)->toMatchArray(['pinned' => true, 'scrub' => true, 'height' => '2720px', 'focusable' => false])
        ->and($tour['progress'])->toBe([0, 0.5, 1, 0])
        ->and($tour['tilts'][0])->toBe(['turn' => 0, 'scale' => 1])
        ->and($tour['tilts'][1])->toBe(['turn' => 26, 'scale' => 0.9])
        ->and($tour['tilts'][2])->toBe(['turn' => -36.4, 'scale' => 0.9]);
});

it('never pins the gallery tour when the visitor asked for less motion: the row keeps scrolling sideways', function () {
    expect(runSiteScript('galleryTour', ['reduced' => true]))->toMatchArray(['pinned' => false, 'scrub' => false, 'height' => null, 'focusable' => true]);
});

/*
 * T031 (CA10, CA18, TM3): the before-and-after comparator with its flat drawing. The browser moves
 * the slider with the arrows, Home and End and fires "input"; the comparator follows it.
 */
it('moves the line of the before and after with the slider and says where it is', function () {
    $seen = runSiteScript('galleryCompare', ['reduced' => true, 'actions' => [['input', 51], ['input', 0], ['input', 100]]])['seen'];

    expect(array_map(fn (array $state): array => [$state['value'], $state['valueNow'], $state['clip'], $state['handle']], $seen))->toBe([
        ['50', '50', 'inset(0 0 0 50%)', '50%'],
        ['51', '51', 'inset(0 0 0 51%)', '51%'],
        ['0', '0', 'inset(0 0 0 0%)', '0%'],
        ['100', '100', 'inset(0 0 0 100%)', '100%'],
    ]);
});

it('changes the treatment, its pressed button and its text from the page', function () {
    $seen = runSiteScript('galleryCompare', ['reduced' => true, 'actions' => [['click', 'braces'], ['click', 'implant'], ['click', 'white']]])['seen'];

    expect(array_column($seen, 'pressed'))->toBe([['true', 'false', 'false'], ['false', 'true', 'false'], ['false', 'false', 'true'], ['true', 'false', 'false']])
        ->and(array_column($seen, 'on'))->toBe([[true, false, false], [false, true, false], [false, false, true], [true, false, false]])
        ->and(array_column($seen, 'title'))->toBe(['Blanqueamiento Dental', 'Ortodoncia', 'Implantes Dentales', 'Blanqueamiento Dental'])
        ->and(array_column($seen, 'text'))->toBe(['Resumen white', 'Resumen braces', 'Resumen implant', 'Resumen white'])
        ->and(array_column($seen, 'sparkles'))->toBe([true, false, false, true]);
});

it('paints the flat drawing of each treatment with the colours of the tokens', function () {
    [$white, $braces, $implant] = runSiteScript('galleryCompare', ['reduced' => true, 'actions' => [['click', 'braces'], ['click', 'implant']]])['seen'];

    expect($white['before'])->toBe(['count' => 16, 'colours' => ['#d9bd85'], 'turned' => 0, 'missing' => 0])
        ->and($white['later'])->toBe(['count' => 16, 'colours' => ['#fefeff'], 'turned' => 0, 'missing' => 0])
        ->and($braces['before']['turned'])->toBeGreaterThan(0)
        ->and($braces['before']['colours'])->toBe(['#f2ebde'])
        ->and($braces['later'])->toBe(['count' => 16, 'colours' => ['#f2ebde'], 'turned' => 0, 'missing' => 0])
        ->and($implant['before']['missing'])->toBe(2)
        ->and($implant['later']['missing'])->toBe(0);
});

it('writes no colour of its own in gallery.js', function () {
    expect(file_get_contents(__DIR__.'/../../../../resources/js/site/gallery.js'))->not->toMatch('/#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?\b/');
});

it('copies the text of a treatment as text, never as markup (abuse)', function () {
    $payload = '<img src=x onerror=alert(1)>';
    $braces = runSiteScript('galleryCompare', ['reduced' => true, 'summaries' => ['braces' => $payload], 'actions' => [['click', 'braces']]])['seen'][1];

    expect($braces['text'])->toBe($payload)
        ->and($braces['textMarkupWrites'])->toBe(0);
});

it('sweeps the line once to show it moves, stops when touched and never with less motion', function () {
    $moving = runSiteScript('galleryCompare', ['reduced' => false, 'frames' => [0, 600, 'touch', 900]]);
    $still = runSiteScript('galleryCompare', ['reduced' => true, 'frames' => [0, 600]]);

    expect($moving['observing'])->toBeTrue()
        ->and($moving['sweep'][0])->toBe('50')
        ->and($moving['sweep'][1])->not->toBe('50')
        ->and($moving['sweep'][2])->toBe('50')
        ->and($moving['pendingFrames'])->toBe(0)
        ->and($still['observing'])->toBeFalse()
        ->and($still['sweep'])->toBe([]);
});

/*
 * T032 (CA18): the 3D denture of the before and after, over a fake WebGL. Its colours come from the
 * tokens (A30); without WebGL or with a shader that does not compile the flat drawing stays.
 */
function siteHexChannels(string $hex): array
{
    return array_map(fn (string $pair): float => round(hexdec($pair) / 255, 3), str_split(ltrim($hex, '#'), 2));
}

it('reads every colour of the denture from its token and follows a change of token', function () {
    $denture = runSiteScript('galleryDenture', ['frames' => [0]]);
    $changed = runSiteScript('galleryDenture', ['frames' => [0], 'tokens' => ['--color-gum' => ' #000000']]);
    $rounded = fn (?array $channels): array => array_map(fn (float $value): float => round($value, 3), $channels ?? []);

    expect($denture['tokens'])->toHaveCount(15)
        ->and(array_diff(array_values($denture['tokens']), $denture['declared']))->toBe([])
        ->and(array_filter($denture['colours'], fn (?array $channels): bool => $channels === null))->toBe([])
        ->and($rounded($denture['colours']['uGum']))->toBe(siteHexChannels('#fcabb5'))
        ->and($rounded($denture['colours']['uMouth']))->toBe(siteHexChannels('#5c1726'))
        ->and($rounded($changed['colours']['uGum']))->toBe([0.0, 0.0, 0.0]);
});

it('draws the denture and hides the flat drawing on its first frame', function () {
    $denture = runSiteScript('galleryDenture', ['frames' => [0]]);

    expect($denture)->toMatchArray(['started' => true, 'on' => true, 'flatOff' => true, 'size' => [1350, 840], 'softwareRefused' => true])
        ->and($denture['drawn'][0]['drew'])->toBeTrue();
});

it('keeps the flat drawing when the browser cannot draw in 3D or the drawing fails', function (string $webgl) {
    expect(runSiteScript('galleryDenture', ['webgl' => $webgl, 'frames' => [0]]))->toMatchArray(['started' => false, 'on' => false, 'flatOff' => false]);
})->with(['without WebGL' => 'none', 'only a drawing by software, which would freeze the page' => 'software', 'a software renderer that does not say so' => 'swiftshader', 'shader that does not compile' => 'broken']);

it('turns the denture slowly only without less motion', function () {
    $turning = array_column(runSiteScript('galleryDenture', ['frames' => [0, 2000, 4000]])['drawn'], 'rot');
    $still = array_column(runSiteScript('galleryDenture', ['reduced' => true, 'frames' => [0, 2000, 4000]])['drawn'], 'rot');

    expect(array_unique(array_map(fn (array $rot): string => json_encode($rot), $turning)))->toHaveCount(3)
        ->and(array_unique(array_map(fn (array $rot): string => json_encode($rot), $still)))->toHaveCount(1);
});

it('follows the line and the treatment of the comparator', function () {
    $frames = range(0, 3000, 50);
    $drawn = runSiteScript('galleryDenture', ['state' => ['mode' => 'braces', 'pos' => 30], 'frames' => $frames])['drawn'];
    $last = collect($drawn)->filter(fn (array $frame): bool => $frame['drew'])->last();

    expect($last['split'])->toBe([0.3])
        ->and($last['before'][1])->toBeGreaterThan(0.9)
        ->and($last['before'][0])->toBeLessThan(0.1)
        ->and($last['after'][0])->toBeLessThan(0.1);
});

it('draws only while the denture is in view and at most about thirty frames a second', function () {
    $hidden = runSiteScript('galleryDenture', ['visible' => false, 'frames' => [0, 100]])['drawn'];
    $fast = array_column(runSiteScript('galleryDenture', ['frames' => [0, 10, 20, 40]])['drawn'], 'drew');

    expect(array_column($hidden, 'drew'))->toBe([false, false])
        ->and($fast)->toBe([true, false, false, true]);
});

it('gives the flat drawing back when the denture cannot keep up', function () {
    $denture = runSiteScript('galleryDenture', ['frames' => [0, 400, 800, 1200, 1600]]);

    expect($denture)->toMatchArray(['started' => true, 'on' => false, 'flatOff' => false])
        ->and(array_column($denture['drawn'], 'drew'))->toBe([true, true, true, true, false]);
});

it('keeps the denture when it draws smoothly', function () {
    $denture = runSiteScript('galleryDenture', ['frames' => range(0, 2000, 40)]);

    expect($denture)->toMatchArray(['on' => true, 'flatOff' => true])
        ->and(end($denture['drawn'])['drew'])->toBeTrue();
});

/*
 * T034 (refined 2026-11-15): on a phone the closing text of the welcome and its fan of photos are
 * centred in the scene, the photos as small as before; on a wide screen nothing changes.
 */
it('centres the welcome text and its fan on a phone, with photos of the same size, and leaves the fan as it was on a wide screen', function () {
    [$phone, $wide] = runSiteScript('fan', ['screens' => [
        ['width' => 390, 'height' => 800, 'cardWidth' => 273, 'cardHeight' => 340, 'textTop' => 100, 'textBottom' => 236, 'count' => 6],
        ['width' => 1440, 'height' => 900, 'cardWidth' => 440, 'cardHeight' => 520, 'textTop' => 118, 'textBottom' => 300, 'count' => 6],
    ]]);
    $fanTop = $phone['baseline'] - $phone['scale'] * 340 / 2;
    $textTop = 100 + $phone['textShift'];
    $fanBottom = $phone['baseline'] + $phone['scale'] * 340 / 2 + $phone['drop'];

    expect($phone['scale'])->toBe(max(0.32, 390 / (4.4 * 273)))
        ->and($phone['textShift'])->toBeGreaterThan(0.0)
        ->and($fanTop - ($textTop + 136))->toEqualWithDelta(24.0, 0.5)
        ->and($textTop)->toEqualWithDelta(800 - $fanBottom, 0.5)
        ->and($wide)->toEqual(['scale' => min(0.56, max(0.32, 1440 / (4.4 * 440))), 'radius' => 1440 * 0.78, 'baseline' => 900 * 0.66, 'textShift' => 0, 'drop' => 0]);
});

/*
 * T041 (CA7, CA10): the phone menu. Opening locks the page, names the button "Cerrar menú", puts
 * the focus inside and keeps it there; Escape, a page link or a wide screen close it; the dock
 * says whether the clinic is open on the clinic's clock.
 */
it('opens the phone menu, locks the page and puts the focus on its first link', function () {
    [$start, $open, $settled] = runSiteScript('phoneMenu', ['actions' => [['click', null], ['flush', null]]]);

    expect($start)->toMatchArray(['open' => false, 'shown' => false, 'expanded' => 'false', 'label' => 'Abrir menú', 'locked' => false])
        ->and($open)->toMatchArray(['prevented' => true, 'open' => true, 'shown' => true, 'expanded' => 'true', 'label' => 'Cerrar menú', 'locked' => true])
        ->and($settled['focused'])->toBe('Inicio');
});

it('keeps the focus inside the open menu with Tab and Shift+Tab', function () {
    $seen = runSiteScript('phoneMenu', ['actions' => [['click', null], ['flush', null], ['focus', 'button'], ['tab', null], ['shiftTab', null], ['focus', 'Galería'], ['tab', null]]]);
    $tabs = array_values(array_filter($seen, fn (array $state): bool => in_array($state['after'], ['tab', 'shiftTab'], true)));

    expect(array_map(fn (array $state): array => [$state['focused'], $state['prevented']], $tabs))->toBe([['Inicio', true], ['button', true], ['Galería', false]]);
});

it('closes the menu with Escape, giving the focus back to its button, after its animation', function () {
    $seen = runSiteScript('phoneMenu', ['actions' => [['click', null], ['flush', null], ['escape', null], ['flush', null]]]);
    [, , , $closing, $closed] = $seen;

    expect($closing)->toMatchArray(['shown' => false, 'open' => true, 'expanded' => 'false', 'label' => 'Abrir menú', 'locked' => false, 'focused' => 'button'])
        ->and($closed['open'])->toBeFalse();
});

it('closes at once with less motion, and when a page link is followed or the screen grows', function () {
    $reduced = runSiteScript('phoneMenu', ['reduced' => true, 'actions' => [['click', null], ['escape', null]]]);
    $link = runSiteScript('phoneMenu', ['actions' => [['click', null], ['flush', null], ['link', null], ['flush', null]]]);
    $wide = runSiteScript('phoneMenu', ['actions' => [['click', null], ['flush', null], ['resize', 1024], ['flush', null]]]);

    expect(end($reduced)['open'])->toBeFalse()
        ->and(end($link))->toMatchArray(['open' => false, 'locked' => false])
        ->and(end($wide))->toMatchArray(['open' => false, 'locked' => false]);
});

it('says in the dock whether the clinic is open, on the clinic clock', function () {
    $open = runSiteScript('phoneMenu', ['at' => '2026-11-16T12:00:00-06:00', 'actions' => [['click', null]]])[1];
    $closed = runSiteScript('phoneMenu', ['at' => '2026-11-16T20:30:00-06:00', 'actions' => [['click', null]]])[1];

    expect($open)->toMatchArray(['statusOpen' => true, 'statusClosed' => false])
        ->and($open['status'])->toStartWith('Abierto ahora · ')
        ->and($closed)->toMatchArray(['statusOpen' => false, 'statusClosed' => true])
        ->and($closed['status'])->toStartWith('Cerrado · ');
});

it('writes the days left on the coupon of the phone menu as on the big coupon (T042)', function () {
    expect(runSiteScript('menuCoupon', ['end' => '2026-11-20', 'at' => '2026-11-15T12:00:00-06:00']))->toBe(['left' => 'Quedan 5 días'])
        ->and(runSiteScript('menuCoupon', ['end' => '2026-11-16', 'at' => '2026-11-15T23:30:00-06:00']))->toBe(['left' => 'Termina mañana'])
        ->and(runSiteScript('menuCoupon', ['end' => '2026-11-15', 'at' => '2026-11-15T09:00:00-06:00']))->toBe(['left' => 'Termina hoy'])
        ->and(runSiteScript('menuCoupon', ['end' => '2026-11-20', 'at' => '2026-11-15T12:00:00-06:00', 'present' => false]))->toBe(['left' => 'Promoción vigente']);
});

it('counts down a single promotion too, from the dates of its coupon (T043)', function () {
    $result = runSiteScript('promos', ['at' => '2026-11-16T12:00:00-06:00', 'tickets' => [], 'card' => ['start' => '2026-11-16', 'end' => '2026-11-23']]);

    expect($result['left'])->toBe('Quedan 7 días')
        ->and($result['bar'])->toBe('93%');
});

it('starts the bar of a promotion going down on its first day, by the clinic clock', function () {
    $bar = fn (string $at): int => runSiteScript('promotion', ['start' => '2026-11-16', 'end' => '2026-11-17', 'at' => $at])['bar'];

    expect($bar('2026-11-16T00:00:00-06:00'))->toBe(100)
        ->and($bar('2026-11-16T12:00:00-06:00'))->toBe(75)
        ->and($bar('2026-11-17T12:00:00-06:00'))->toBe(25)
        ->and($bar('2026-11-17T23:59:00-06:00'))->toBe(4)
        ->and($bar('2026-11-16T18:00:00Z'))->toBe(75);
});

/*
 * T023 (CA6, CA16; TM3, A35): the WhatsApp composer of the contact page. A motive writes its message,
 * which the visitor can edit; sending opens wa.me with the message encoded and never posts the form;
 * an empty message opens nothing and marks the field.
 */
const COMPOSER_MOTIVES = ['Hola, me gustaría agendar una cita de ortodoncia. ¿Qué horarios tienen disponibles?', 'Hola, tengo una pregunta: '];

it('writes the message of the chosen motive and marks it as pressed', function () {
    [$chosen, $other] = runSiteScript('composer', ['motives' => COMPOSER_MOTIVES, 'value' => COMPOSER_MOTIVES[0], 'actions' => [['chip', 0], ['chip', 1]]]);

    expect($chosen)->toMatchArray(['value' => COMPOSER_MOTIVES[0], 'pressed' => ['true', 'false'], 'on' => [true, false]])
        ->and($other)->toMatchArray(['value' => COMPOSER_MOTIVES[1], 'pressed' => ['false', 'true'], 'on' => [false, true], 'focused' => 'textarea', 'selection' => [26, 26]]);
});

it('opens WhatsApp in a new tab with the message encoded, and never posts the form', function () {
    $message = 'Hola <img src=x onerror=alert(1)> & "comillas" #1';
    [$typed, $sent] = runSiteScript('composer', ['number' => '5215598765432', 'motives' => COMPOSER_MOTIVES, 'actions' => [['type', "  {$message}  "], ['submit', null]]]);

    $url = $sent['opened'][0]['url'] ?? '';
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($sent['prevented'])->toBeTrue()
        ->and($sent['opened'])->toHaveCount(1)
        ->and($sent['opened'][0])->toMatchArray(['target' => '_blank', 'features' => 'noopener,noreferrer'])
        ->and(strtok($url, '?'))->toBe('https://wa.me/5215598765432')
        ->and($query)->toBe(['text' => $message])
        ->and(substr($url, strpos($url, '?') + 1))->not->toMatch('/[<>"&# ]/')
        ->and($sent['requests'])->toBe(0)
        ->and($sent['created'])->toBe(0)
        ->and($sent['invalid'])->toBeNull()
        ->and($sent['errorShown'])->toBeFalse();
});

it('opens nothing with an empty message: it marks the field and says why, until something is written', function () {
    [, $empty, $typed, $sent] = runSiteScript('composer', ['motives' => COMPOSER_MOTIVES, 'actions' => [['type', "   \n "], ['submit', null], ['type', 'Hola'], ['submit', null]]]);

    expect($empty)->toMatchArray(['prevented' => true, 'opened' => [], 'invalid' => 'true', 'errorShown' => true, 'focused' => 'textarea', 'requests' => 0])
        ->and($typed)->toMatchArray(['invalid' => null, 'errorShown' => false])
        ->and($sent['opened'])->toHaveCount(1);
});

it('pauses the services ribbon with its button, holds it while it has the focus and toggles it with a touch (R1)', function () {
    $seen = runSiteScript('marquee', ['actions' => ['click', 'click', 'focusin', 'focusout', 'touch', 'touch', 'mouse']]);

    expect(array_column($seen, 'held'))->toBe([true, false, true, false, true, false, false])
        ->and(array_column($seen, 'pressed'))->toBe(['true', 'false', 'false', 'false', 'true', 'false', 'false'])
        ->and(array_unique(array_column($seen, 'label')))->toBe(['Pausar la cinta de servicios']);
});

it('never leaves a hidden block of the welcome in the way of the keyboard: what cannot be seen is inert (R3)', function () {
    $steps = runSiteScript('welcomeFade', ['progress' => [0, 0.1, 0.3, 0.5, 0.8, 0.9, 1]]);

    expect(array_column($steps, 'copyInert'))->toBe([false, false, true, true, true, true, true])
        ->and(array_column($steps, 'endInert'))->toBe([true, true, true, true, true, false, false]);

    foreach ($steps as $step) {
        expect($step['copyInert'] || $step['copyOpacity'] >= 0.5)->toBeTrue()
            ->and($step['copyOpacity'] > 0 || $step['copyInert'])->toBeTrue()
            ->and($step['endShown'] > 0 || $step['endInert'])->toBeTrue();
    }
});

it('announces a testimonial only when the visitor asks for it, never when it changes on its own (R4)', function () {
    $seen = runSiteScript('testimonialsLive', ['actions' => ['auto', 'next', 'auto', 'prev', 'dot2', 'auto']]);

    expect(array_column($seen, 'current'))->toBe([1, 2, 0, 2, 2, 0])
        ->and(array_column($seen, 'live'))->toBe(['off', 'polite', 'off', 'polite', 'polite', 'off']);
});

it('says in the status region what was copied, then clears it (R6)', function () {
    $result = runSiteScript('copy', ['press' => ['phone', 'address']]);

    expect($result['copied'])->toBe(['+52 55 1234 5678', 'Lázaro Cárdenas, Michoacán'])
        ->and(array_column($result['seen'], 'region'))->toBe(['Teléfono copiado', 'Dirección copiada'])
        ->and(array_column($result['seen'], 'shown'))->toBe([true, true])
        ->and($result['seen'][1]['label'])->toBe('Dirección copiada')
        ->and($result['regionAfter'])->toBe('')
        ->and($result['shownAfter'])->toBeFalse();
});

it('pauses the ribbon once when a finger lands on the icon of its button, and resumes it while the button keeps the focus (R31, R33)', function () {
    $seen = runSiteScript('marquee', ['actions' => ['tapIcon', 'tapIcon', 'focusButton', 'click', 'click', 'focusout', 'focusin']]);

    expect(array_column($seen, 'pressed'))->toBe(['true', 'false', 'false', 'true', 'false', 'false', 'false'])
        ->and(array_column($seen, 'held'))->toBe([true, false, false, true, false, false, true]);
});
