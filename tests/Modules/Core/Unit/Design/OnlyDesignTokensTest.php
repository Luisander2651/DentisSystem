<?php

declare(strict_types=1);

/*
 * Spec 016, CA1, CA2, CA5, CA8, CA24, CA29 (P15): a view or a page script takes its colours,
 * radii and control sizes from the design tokens. Every file of resources/views and
 * resources/js that is not in pending-files.php must respect every rule below; a pending file
 * may only use colours that still exist (tokens or the transitional block of app.css), so
 * nothing loses its colour unnoticed while the migration lasts (A40).
 */

const DESIGN_ROOT = __DIR__.'/../../../../..';

const DESIGN_COLOR_PREFIXES = 'bg|text|border(?:-[trblxyse])?|ring(?:-offset)?|outline|from|via|to|fill|stroke|shadow|decoration|divide|placeholder|accent|caret';

const DESIGN_DEFAULT_PALETTE = 'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose';

const DESIGN_RADII = ['none', 'control', 'box', 'card', 'full'];

/**
 * @return list<string>
 */
function designPendingFiles(): array
{
    return require __DIR__.'/pending-files.php';
}

/**
 * Every view and script of the interface, relative to the repository root.
 *
 * @return list<string>
 */
function designInterfaceFiles(): array
{
    $files = [];

    foreach (['resources/views' => '.blade.php', 'resources/js' => '.js'] as $directory => $suffix) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DESIGN_ROOT.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $files[] = $directory.str_replace('\\', '/', substr($file->getPathname(), strlen(DESIGN_ROOT.'/'.$directory)));
            }
        }
    }

    sort($files);

    return $files;
}

/**
 * The colour token names of docs/design/system.md, without the `--color-` prefix.
 *
 * @return list<string>
 */
function designColorTokens(): array
{
    preg_match_all('/^\| `--color-([a-z-]+)` \|/m', (string) file_get_contents(DESIGN_ROOT.'/docs/design/system.md'), $matches);

    return $matches[1];
}

/**
 * The opening tag that holds the given offset, as far as its closing `>`.
 */
function designTagAround(string $content, int $offset): string
{
    $start = strrpos(substr($content, 0, $offset), '<');
    $start = $start === false ? 0 : $start;

    for ($end = $offset; $end < strlen($content); $end++) {
        if ($content[$end] === '>' && ! in_array($content[$end - 1], ['-', '='], true)) {
            break;
        }
    }

    return substr($content, $start, $end - $start);
}

/**
 * Each rule returns what it found wrong in a file's content.
 *
 * @return array<string, Closure(string): list<string>>
 */
function designRules(): array
{
    $prefixes = DESIGN_COLOR_PREFIXES;
    $palette = DESIGN_DEFAULT_PALETTE;
    $tokens = implode('|', array_map(fn (string $token): string => preg_quote($token, '/'), designColorTokens()));

    $matches = fn (string $pattern): Closure => function (string $content) use ($pattern): array {
        preg_match_all($pattern, $content, $found);

        return array_values(array_unique($found[0]));
    };

    return [
        'literal colour' => $matches('/(?<![&\w])#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{3,4})(?![\w-])|\b(?:rgba?|hsla?|oklch|oklab)\(/'),

        'arbitrary colour utility' => $matches('/(?<![\w-])(?:'.$prefixes.')-\[(?:#|rgb|hsl|oklch|color:|var\(--color|(?:linear|radial|conic)-gradient)[^\]]*\]/'),

        'colour utility that is not a token' => $matches('/(?<![\w-])(?:'.$prefixes.')-(?:(?:'.$palette.')-\d{2,3}|white|black)(?:\/\d+)?(?![\w-])/'),

        'pink as text colour outside data-accent' => function (string $content): array {
            preg_match_all('/(?<![\w-])text-(?:primary(?:-hover|-soft)?|secondary)(?![\w-])/', $content, $found, PREG_OFFSET_CAPTURE);
            $wrong = [];

            foreach ($found[0] as [$utility, $offset]) {
                if ($utility !== 'text-primary' || ! str_contains(designTagAround($content, $offset), 'data-accent')) {
                    $wrong[] = $utility;
                }
            }

            return array_values(array_unique($wrong));
        },

        'opacity on a text colour' => $matches('/(?<![\w-])text-(?:'.$tokens.'|transparent|current|inherit)\/\d+/'),

        'font size under 12 px' => function (string $content): array {
            preg_match_all('/(?<![\w-])text-\[(\d*\.?\d+)(px|rem|em)\]/', $content, $found, PREG_SET_ORDER);

            return array_values(array_unique(array_map(
                fn (array $match): string => $match[0],
                array_filter($found, fn (array $match): bool => (float) $match[1] * ($match[2] === 'px' ? 1 : 16) < 12),
            )));
        },

        'focus without outline' => $matches('/(?<![\w-])outline-(?:none|hidden)(?![\w-])/'),

        'radius outside the scale' => function (string $content): array {
            preg_match_all('/(?<![\w-])rounded(?:-[trblse]{1,2})?(?:-(\[[^\]]+\]|[a-z0-9]+))?(?![\w-])/', $content, $found, PREG_SET_ORDER);

            return array_values(array_unique(array_map(
                fn (array $match): string => $match[0],
                array_filter($found, fn (array $match): bool => ! in_array($match[1] ?? '', DESIGN_RADII, true)),
            )));
        },
    ];
}

/**
 * @return array<string, list<string>>
 */
function designViolations(string $content): array
{
    return array_filter(array_map(fn (Closure $rule): array => $rule($content), designRules()));
}

it('reads the colour tokens from the design system', function () {
    expect(designColorTokens())->toContain('primary', 'ink', 'muted', 'on-dark', 'danger-soft', 'whatsapp');
});

it('catches every forbidden pattern (positive controls)', function (string $rule, string $sample) {
    expect(designRules()[$rule]($sample))->not->toBeEmpty();
})->with([
    'hex colour' => ['literal colour', '<div style="color: #E91E63"></div>'],
    'short hex colour' => ['literal colour', "button.style.background = '#fff';"],
    'rgb colour' => ['literal colour', 'box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1)'],
    'arbitrary background' => ['arbitrary colour utility', 'class="bg-[#E91E63]"'],
    'arbitrary border with a variable' => ['arbitrary colour utility', 'class="hover:border-[var(--color-primary)]"'],
    'arbitrary gradient' => ['arbitrary colour utility', 'class="bg-[linear-gradient(90deg,red,blue)]"'],
    'default palette text' => ['colour utility that is not a token', 'class="text-slate-400"'],
    'default palette with a variant' => ['colour utility that is not a token', 'class="md:hover:bg-red-600/90"'],
    'default palette gradient stop' => ['colour utility that is not a token', 'class="bg-linear-to-r from-pink-50 to-surface"'],
    'white' => ['colour utility that is not a token', 'class="bg-white"'],
    'black with opacity' => ['colour utility that is not a token', 'class="bg-black/40"'],
    'pink text' => ['pink as text colour outside data-accent', '<a class="font-semibold text-primary" href="/x">Ver</a>'],
    'pink text next to an accent container' => ['pink as text colour outside data-accent', '<span data-accent></span><a class="text-primary">Ver</a>'],
    'soft pink text' => ['pink as text colour outside data-accent', '<span data-accent class="text-primary-soft"></span>'],
    'secondary text' => ['pink as text colour outside data-accent', '<span data-accent class="text-secondary"></span>'],
    'text opacity' => ['opacity on a text colour', 'class="text-ink/70"'],
    'muted text opacity' => ['opacity on a text colour', 'class="placeholder:text-muted/50"'],
    '10 px text' => ['font size under 12 px', 'class="text-[10px]"'],
    '11 px text' => ['font size under 12 px', 'class="sm:text-[11px]"'],
    'text under 0.75 rem' => ['font size under 12 px', 'class="text-[0.7rem]"'],
    'focus:outline-none' => ['focus without outline', 'class="focus:outline-none"'],
    'outline-hidden' => ['focus without outline', 'class="focus-visible:outline-hidden"'],
    'default radius' => ['radius outside the scale', 'class="rounded border"'],
    'large radius' => ['radius outside the scale', 'class="rounded-3xl"'],
    'arbitrary radius' => ['radius outside the scale', 'class="rounded-[32px]"'],
    'side radius outside the scale' => ['radius outside the scale', 'class="rounded-t-2xl"'],
]);

it('accepts what the design system allows (negative controls)', function (string $sample) {
    expect(designViolations($sample))->toBe([]);
})->with([
    'token utilities' => ['<button class="rounded-control bg-primary text-ink hover:bg-primary-hover border border-field">Guardar</button>'],
    'opacity on backgrounds, borders and shadows' => ['<div class="bg-surface/90 border-on-dark/20 shadow-lg shadow-primary/20"></div>'],
    'transparent, current and inherit' => ['<svg class="fill-current stroke-current text-inherit border-transparent bg-transparent"></svg>'],
    'gradient between tokens' => ['<div class="bg-linear-to-br from-secondary via-primary-soft to-surface"></div>'],
    'accent icon' => ['<span data-accent class="inline-flex text-primary"><svg></svg></span>'],
    'accent icon in a script' => ["html += '<span data-accent class=\"text-primary\">' + icon + '</span>';"],
    'radii of the scale' => ['<div class="rounded-card rounded-t-box rounded-full rounded-none sm:rounded-control"></div>'],
    'text size utilities' => ['<p class="text-sm/6 text-min text-control text-[13px] text-center">Texto</p>'],
    'anchors, entities and ids' => ['<a href="#">&#039; &#10003;</a> document.querySelector("#add-fee")'],
    'focus ring of the base layer' => ['<input class="outline-ink focus-visible:outline-3">'],
]);

it('only lists pending files that exist', function () {
    $missing = array_filter(designPendingFiles(), fn (string $file): bool => ! is_file(DESIGN_ROOT.'/'.$file));

    expect(array_values($missing))->toBe([])
        ->and(designPendingFiles())->toBe(array_values(array_unique(designPendingFiles())));
});

it('uses only design tokens in every migrated view and script', function () {
    $violations = [];

    foreach (array_diff(designInterfaceFiles(), designPendingFiles()) as $file) {
        $found = designViolations((string) file_get_contents(DESIGN_ROOT.'/'.$file));

        if ($found !== []) {
            $violations[$file] = $found;
        }
    }

    expect($violations)->toBe([]);
});

it('keeps every colour a pending file uses alive in the stylesheet', function () {
    $stylesheet = (string) file_get_contents(DESIGN_ROOT.'/resources/css/app.css');
    $rule = designRules()['colour utility that is not a token'];
    $lost = [];

    foreach (designPendingFiles() as $file) {
        foreach ($rule((string) file_get_contents(DESIGN_ROOT.'/'.$file)) as $utility) {
            $colour = preg_replace(['/^(?:'.DESIGN_COLOR_PREFIXES.')-/', '/\/\d+$/'], '', $utility);

            if (! str_contains($stylesheet, '--color-'.$colour.':')) {
                $lost[$colour][] = $file;
            }
        }
    }

    expect(array_keys($lost))->toBe([]);
})->skip('016: pendiente de T025');
