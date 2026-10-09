<?php

declare(strict_types=1);

/*
 * Spec 016, CA2, CA3, CA4, CA6, CA7, CA38: the design tokens exist once, in the `@theme` of
 * resources/css/app.css, with the values docs/design/system.md documents; every pair the
 * system relies on reaches its contrast; Tailwind's default palette is gone, so a colour
 * outside the system does not even compile; and no two tokens fight for the same utility.
 *
 * While views are still being migrated, `@theme` keeps the old colour names they use between
 * the markers TRANSITIONAL_START and TRANSITIONAL_END. That block must be gone once
 * pending-files.php is empty.
 */

const TOKENS_ROOT = __DIR__.'/../../../../..';

const TRANSITIONAL_START = '/* 016-transitorio:inicio */';

const TRANSITIONAL_END = '/* 016-transitorio:fin */';

/** [foreground, background, minimum]: text needs 4.5:1; borders, focus and icons, 3:1. */
const TOKEN_PAIRS = [
    'text on the main action' => ['ink', 'primary', 4.5],
    'text on the hovered main action' => ['ink', 'primary-hover', 4.5],
    'text on the active item and notices' => ['ink', 'primary-soft', 4.5],
    'text on a card' => ['ink', 'surface', 4.5],
    'text on the page' => ['ink', 'canvas', 4.5],
    'secondary text and placeholders on a card' => ['muted', 'surface', 4.5],
    'secondary text on the page' => ['muted', 'canvas', 4.5],
    'secondary text on a notice' => ['muted', 'primary-soft', 4.5],
    'text on the delete action' => ['on-dark', 'danger', 4.5],
    'text on a dark surface' => ['on-dark', 'ink', 4.5],
    'error on its soft background' => ['danger', 'danger-soft', 4.5],
    'field error on a card' => ['danger', 'surface', 4.5],
    'completed and active states' => ['success', 'success-soft', 4.5],
    'assigned state' => ['warning', 'warning-soft', 4.5],
    'rescheduled state' => ['info', 'info-soft', 4.5],
    'text on the WhatsApp button' => ['ink', 'whatsapp', 4.5],
    'field border on a card' => ['field', 'surface', 3.0],
    'field border on the page' => ['field', 'canvas', 3.0],
    'focus outline on a card' => ['ink', 'surface', 3.0],
    'focus outline on a dark surface' => ['on-dark', 'ink', 3.0],
    'pink as an accent' => ['primary', 'surface', 3.0],
    'action icons on a card' => ['muted', 'surface', 3.0],
];

function tokensStylesheet(): string
{
    return (string) file_get_contents(TOKENS_ROOT.'/resources/css/app.css');
}

/**
 * The stylesheet without the transitional block.
 */
function tokensWithoutTransitional(string $css): string
{
    return (string) preg_replace('/'.preg_quote(TRANSITIONAL_START, '/').'.*?'.preg_quote(TRANSITIONAL_END, '/').'/s', '', $css);
}

/**
 * The custom properties a stylesheet declares inside `@theme`, by name.
 *
 * @return array<string, string>
 */
function tokensDeclared(string $css): array
{
    preg_match_all('/@theme\s*\{(.*?)\n\}/s', $css, $blocks);
    preg_match_all('/(--[a-z0-9-]+|--[a-z]+-\*)\s*:\s*([^;]+);/', implode("\n", $blocks[1]), $declarations, PREG_SET_ORDER);

    $tokens = [];

    foreach ($declarations as [, $name, $value]) {
        $tokens[$name] = strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }

    return $tokens;
}

/**
 * The rows of a token table of docs/design/system.md: token name => documented value.
 *
 * @return array<string, string>
 */
function tokensDocumented(string $prefix): array
{
    preg_match_all('/^\| `(--'.$prefix.'-[a-z-]+)` \| `?([^|`]+?)`? \|/m', (string) file_get_contents(TOKENS_ROOT.'/docs/design/system.md'), $rows, PREG_SET_ORDER);

    $tokens = [];

    foreach ($rows as [, $name, $value]) {
        $tokens[$name] = strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }

    return $tokens;
}

/**
 * @return array{float, float, float}
 */
function tokenRgb(string $hex): array
{
    return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
}

function tokenContrast(string $foreground, string $background): float
{
    $luminance = function (string $hex): float {
        [$red, $green, $blue] = array_map(function (float $value): float {
            $channel = $value / 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, tokenRgb($hex));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    };

    $lights = [$luminance($foreground), $luminance($background)];

    return (max($lights) + 0.05) / (min($lights) + 0.05);
}

/**
 * Tokens of two namespaces that would generate the same utility: `--color-x` and `--text-x`
 * both answer to `text-x`.
 *
 * @param  array<string, string>  $tokens
 * @return list<string>
 */
function tokenCollisions(array $tokens): array
{
    $names = fn (string $prefix): array => array_map(
        fn (string $token): string => substr($token, strlen($prefix)),
        array_values(array_filter(array_keys($tokens), fn (string $token): bool => str_starts_with($token, $prefix) && ! str_ends_with($token, '*'))),
    );

    return array_values(array_intersect($names('--color-'), $names('--text-')));
}

it('computes contrast the way WCAG does (controls)', function () {
    expect(round(tokenContrast('#000000', '#ffffff'), 2))->toBe(21.0)
        ->and(round(tokenContrast('#0b1120', '#d75078'), 2))->toBe(4.76)
        ->and(round(tokenContrast('#ffffff', '#e91e63'), 2))->toBe(4.35);
});

it('detects two tokens that would share a utility (control)', function () {
    $css = "@theme {\n    --color-field: #7c8aa0;\n    --text-field: 16px;\n    --text-control: 16px;\n    --radius-control: 12px;\n}";

    expect(tokenCollisions(tokensDeclared($css)))->toBe(['field']);
});

it('declares every documented token with its documented value', function (string $prefix) {
    $declared = tokensDeclared(tokensWithoutTransitional(tokensStylesheet()));
    $documented = tokensDocumented($prefix);

    expect($documented)->not->toBeEmpty();

    foreach ($documented as $name => $value) {
        expect($declared[$name] ?? 'sin declarar')->toBe($value, $name);
    }
})->with(['color', 'radius', 'spacing', 'text'])->skip('016: pendiente de T025');

it('declares no colour the design system does not document', function () {
    $declared = array_filter(
        array_keys(tokensDeclared(tokensWithoutTransitional(tokensStylesheet()))),
        fn (string $token): bool => str_starts_with($token, '--color-') && $token !== '--color-*',
    );

    expect(array_values(array_diff($declared, array_keys(tokensDocumented('color')))))->toBe([]);
})->skip('016: pendiente de T025');

it('reaches the contrast every pair of the system needs', function (string $foreground, string $background, float $minimum) {
    $declared = tokensDeclared(tokensStylesheet());

    expect(tokenContrast($declared['--color-'.$foreground], $declared['--color-'.$background]))->toBeGreaterThanOrEqual($minimum);
})->with(TOKEN_PAIRS)->skip('016: pendiente de T025');

it('documents the contrast each token really has', function () {
    $declared = tokensDeclared(tokensStylesheet());
    $wrong = [];

    preg_match_all('/^\| `--color-([a-z-]+)` \|.*$/m', (string) file_get_contents(TOKENS_ROOT.'/docs/design/system.md'), $rows, PREG_SET_ORDER);

    foreach ($rows as [$row, $token]) {
        preg_match_all('/(\d+\.\d+):1 sobre `--color-([a-z-]+)`/', $row, $over, PREG_SET_ORDER);
        preg_match_all('/`--color-([a-z-]+)` encima (\d+\.\d+):1/', $row, $under, PREG_SET_ORDER);

        $claims = [
            ...array_map(fn (array $match): array => [$token, $match[2], (float) $match[1]], $over),
            ...array_map(fn (array $match): array => [$match[1], $token, (float) $match[2]], $under),
        ];

        foreach ($claims as [$foreground, $background, $documented]) {
            $real = round(tokenContrast($declared['--color-'.$foreground], $declared['--color-'.$background]), 2);

            if (abs($real - $documented) > 0.011) {
                $wrong[] = "$foreground sobre $background: documentado $documented, real $real";
            }
        }
    }

    expect($wrong)->toBe([]);
})->skip('016: pendiente de T025');

it('removes the default palette of Tailwind', function () {
    $declared = tokensDeclared(tokensStylesheet());

    expect($declared['--color-*'] ?? null)->toBe('initial')
        ->and(array_key_exists('--color-white', tokensDeclared(tokensWithoutTransitional(tokensStylesheet()))))->toBeFalse();
})->skip('016: pendiente de T025');

it('lets no two tokens share a utility', function () {
    expect(tokenCollisions(tokensDeclared(tokensStylesheet())))->toBe([]);
});

it('keeps the transitional colours only while files are pending', function () {
    $pending = require __DIR__.'/pending-files.php';
    $hasBlock = str_contains(tokensStylesheet(), TRANSITIONAL_START);

    expect(substr_count(tokensStylesheet(), TRANSITIONAL_START))->toBe(substr_count(tokensStylesheet(), TRANSITIONAL_END))
        ->and($pending === [] && $hasBlock)->toBeFalse();
});
