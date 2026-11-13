<?php

declare(strict_types=1);

/*
 * Spec 026, CA1, CA8 and CA17 (P15; A1, A11, A12, A20, A30 of /analyze 026): the public site keeps
 * its own stylesheets and scripts, but every colour, radius, control height, font and curve comes
 * from a token of resources/css/app.css; nothing is loaded from another site; and its scripts name
 * no treatment the clinic does not offer. Each rule has a positive control (it catches a literal)
 * and a negative one (it lets the allowed form through).
 */

const SITE_STYLES_ROOT = __DIR__.'/../../../../..';

const SITE_CSS_DIR = 'resources/css/site';

const SITE_JS_DIR = 'resources/js/site';

const SITE_VIEW_DIRS = ['resources/views/site', 'resources/views/components/site', 'resources/views/layouts/site.blade.php'];

const SITE_FONTS_DIR = 'resources/fonts';

/** Treatments Dentissa does not offer (CA17): none of them may appear in the site's scripts. */
const SITE_DISCARDED_TREATMENTS = ['endodoncia', 'periodoncia', 'carilla', 'corona', 'prótesis', 'protesis', 'diseño de sonrisa', 'cirugía maxilofacial', 'bruxismo'];

/** Selectors of controls whose height must come from a token. */
const SITE_CONTROL_SELECTOR = '/(^|[\s.,>+~(])(btn[\w-]*|seg[\w-]*|input|select|textarea|field[\w-]*)\b/i';

/**
 * Files under a path of the repository with the given suffix (the path may be a file).
 *
 * @return array<string, string> relative path => content
 */
function siteFiles(string $path, string $suffix): array
{
    $absolute = SITE_STYLES_ROOT.'/'.$path;

    if (is_file($absolute)) {
        return str_ends_with($absolute, $suffix) ? [$path => (string) file_get_contents($absolute)] : [];
    }

    if (! is_dir($absolute)) {
        return [];
    }

    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (str_ends_with($file->getFilename(), $suffix)) {
            $relative = $path.str_replace('\\', '/', substr($file->getPathname(), strlen($absolute)));
            $files[$relative] = (string) file_get_contents($file->getPathname());
        }
    }

    ksort($files);

    return $files;
}

/**
 * @return array<string, string>
 */
function siteViews(): array
{
    return array_merge(...array_map(fn (string $path): array => siteFiles($path, '.blade.php'), SITE_VIEW_DIRS));
}

function siteWithoutComments(string $css): string
{
    return (string) preg_replace('#/\*.*?\*/#s', '', $css);
}

function siteWithoutFontFaces(string $css): string
{
    return (string) preg_replace('/@font-face\s*\{[^}]*\}/s', '', $css);
}

/**
 * Literal colours in a piece of code: hexadecimal, rgb(), rgba(), hsl() or hsla().
 *
 * @return list<string>
 */
function siteColourLiterals(string $code): array
{
    preg_match_all('/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?)\s*\(/i', $code, $matches);

    return $matches[0];
}

/**
 * `border-radius` values written in pixels (a percentage, `inherit` or a var() is allowed).
 *
 * @return list<string>
 */
function siteRadiusLiterals(string $css): array
{
    preg_match_all('/border(?:-[a-z]+)*-radius\s*:\s*([^;}]+)/i', $css, $matches);

    return array_values(array_filter($matches[1], fn (string $value): bool => (bool) preg_match('/\b(?!0px)\d+(?:\.\d+)?px\b/', preg_replace('/var\([^)]*\)/', '', $value))));
}

/**
 * Heights in pixels on the selectors of controls.
 *
 * @return list<string>
 */
function siteControlHeightLiterals(string $css): array
{
    $found = [];

    preg_match_all('/([^{}]+)\{([^{}]*)\}/', siteWithoutComments($css), $rules, PREG_SET_ORDER);

    foreach ($rules as [, $selector, $body]) {
        if (! preg_match(SITE_CONTROL_SELECTOR, $selector)) {
            continue;
        }

        if (preg_match_all('/(?<![\w-])(min-height|height)\s*:\s*(\d+(?:\.\d+)?px)/i', $body, $heights, PREG_SET_ORDER)) {
            foreach ($heights as [$declaration]) {
                $found[] = trim($selector).' { '.$declaration.' }';
            }
        }
    }

    return $found;
}

/**
 * `font-family` declarations that do not use a variable or inherit, outside `@font-face`.
 *
 * @return list<string>
 */
function siteFontFamilyLiterals(string $css): array
{
    preg_match_all('/font-family\s*:(?!\s*(?:var\(|inherit\b))\s*([^;}]+)/i', siteWithoutFontFaces(siteWithoutComments($css)), $matches);

    return $matches[1];
}

/**
 * Custom properties a stylesheet reads with var() that neither the tokens nor the site declare.
 *
 * @param  list<string>  $declaredElsewhere
 * @return list<string>
 */
function siteUndeclaredVariables(string $css, array $declaredElsewhere): array
{
    preg_match_all('/var\(\s*(--[\w-]+)/', $css, $used);
    preg_match_all('/(--[\w-]+)\s*:/', $css, $local);

    return array_values(array_unique(array_diff($used[1], $local[1], $declaredElsewhere)));
}

/**
 * Custom properties declared by the tokens (every @theme block of app.css) and by the views and
 * scripts of the site (e.g. `style="--i: 2"` or `el.style.setProperty('--x', …)`).
 *
 * @return list<string>
 */
function siteDeclaredVariables(): array
{
    preg_match_all('/(--[\w-]+)\s*:/', (string) file_get_contents(SITE_STYLES_ROOT.'/resources/css/app.css'), $tokens);

    $elsewhere = implode("\n", [...siteViews(), ...siteFiles(SITE_JS_DIR, '.js')]);
    preg_match_all('/(--[\w-]+)\s*:|setProperty\(\s*[\'"](--[\w-]+)/', $elsewhere, $site);

    return array_values(array_filter(array_unique([...$tokens[1], ...$site[1], ...$site[2]])));
}

/**
 * Colours written in the markup of a view: in a style attribute or as fill/stroke.
 *
 * @return list<string>
 */
function siteMarkupColours(string $view): array
{
    preg_match_all('/style\s*=\s*"([^"]*)"/i', $view, $styles);
    preg_match_all('/\b(?:fill|stroke|stop-color)\s*=\s*"([^"]*)"/i', $view, $paints);

    $found = [];

    foreach ($styles[1] as $style) {
        array_push($found, ...siteColourLiterals($style));
    }

    foreach ($paints[1] as $paint) {
        if (! preg_match('/^(none|currentColor|transparent|url\(.*\)|var\(.*\))$/i', trim($paint))) {
            $found[] = $paint;
        }
    }

    return $found;
}

/**
 * Resources requested from another site by a stylesheet or a view.
 *
 * @return list<string>
 */
function siteExternalResources(string $code): array
{
    preg_match_all('/@import\s+(?:url\()?\s*[\'"]?(https?:)?\/\/[^\s\'")]+|url\(\s*[\'"]?(?:https?:)?\/\/[^\s\'")]+|<link\b[^>]*\bhref\s*=\s*"(?:https?:)?\/\/[^"]+"|<script\b[^>]*\bsrc\s*=\s*"(?:https?:)?\/\/[^"]+"/i', $code, $matches);

    return $matches[0];
}

/**
 * @return list<string>
 */
function siteDiscardedTreatments(string $code): array
{
    $lower = mb_strtolower($code);

    return array_values(array_filter(SITE_DISCARDED_TREATMENTS, fn (string $term): bool => str_contains($lower, $term)));
}

it('catches every forbidden form (positive controls)', function (string $rule, string $sample) {
    $found = match ($rule) {
        'colour' => siteColourLiterals($sample),
        'radius' => siteRadiusLiterals($sample),
        'control height' => siteControlHeightLiterals($sample),
        'font family' => siteFontFamilyLiterals($sample),
        'undeclared variable' => siteUndeclaredVariables($sample, ['--color-ink']),
        'markup colour' => siteMarkupColours($sample),
        'external resource' => siteExternalResources($sample),
        'treatment' => siteDiscardedTreatments($sample),
    };

    expect($found)->not->toBeEmpty();
})->with([
    'hexadecimal colour' => ['colour', '.a{color:#fff}'],
    'rgb colour' => ['colour', '.a{background:rgb(11 17 32 / .45)}'],
    'hsl colour' => ['colour', "ctx.fillStyle = 'hsl(10, 50%, 50%)'"],
    'colour built in a script' => ['colour', "ctx.fillStyle = 'rgb(' + p.c + ')'"],
    'radius in pixels' => ['radius', '.card{border-radius:28px}'],
    'corner radius in pixels' => ['radius', '.tab{border-top-left-radius:10px}'],
    'button height' => ['control height', '.btn-hdr{min-height:46px;padding:0 18px}'],
    'segment height' => ['control height', '.hz .seg-b{height:40px}'],
    'font family outside @font-face' => ['font family', ".logo{font-family:'Inter Tight',sans-serif}"],
    'undeclared variable' => ['undeclared variable', '.a{color:var(--primary-deep)}'],
    'colour in a style attribute' => ['markup colour', '<h2 style="color: #fff">Hola</h2>'],
    'colour in an svg stroke' => ['markup colour', '<path stroke="#f2b0a6" d="M0 0"/>'],
    'stylesheet from another site' => ['external resource', "@import url('https://fonts.googleapis.com/css2?family=Inter')"],
    'font from another site' => ['external resource', '@font-face{src:url(https://fonts.gstatic.com/x.woff2)}'],
    'link to another site' => ['external resource', '<link rel="stylesheet" href="https://fonts.googleapis.com/css2">'],
    'script from another site' => ['external resource', '<script src="//cdn.example.com/x.js"></script>'],
    'discarded treatment' => ['treatment', "var BA_INFO = { name: 'Carillas de porcelana' }"],
]);

it('lets the allowed forms through (negative controls)', function (string $rule, string $sample) {
    $found = match ($rule) {
        'colour' => siteColourLiterals($sample),
        'radius' => siteRadiusLiterals($sample),
        'control height' => siteControlHeightLiterals($sample),
        'font family' => siteFontFamilyLiterals($sample),
        'undeclared variable' => siteUndeclaredVariables($sample, ['--color-ink']),
        'markup colour' => siteMarkupColours($sample),
        'external resource' => siteExternalResources($sample),
        'treatment' => siteDiscardedTreatments($sample),
    };

    expect($found)->toBe([]);
})->with([
    'token colour' => ['colour', '.a{color:var(--color-ink);background:color-mix(in srgb, var(--color-ink) 45%, transparent)}'],
    'anchor that looks like a colour word' => ['colour', '<a href="#contenido">Saltar</a>'],
    'radius from a token' => ['radius', '.card{border-radius:var(--radius-xl)}'],
    'round shape' => ['radius', '.dot{border-radius:50%}'],
    'tooth shape' => ['radius', '.ba{border-radius:50% 50% 50% 50% / 46% 46% 54% 54%}'],
    'no radius' => ['radius', '.flat{border-radius:0}'],
    'control height from a token' => ['control height', '.btn{min-height:var(--spacing-control)}'],
    'decorative bar height' => ['control height', '.hz-bar i{height:6px}'],
    'font family inside @font-face' => ['font family', "@font-face{font-family:'Inter Tight';src:url('../../fonts/inter-tight-400.woff2')}"],
    'font family from a token' => ['font family', '.h-xl{font-family:var(--font-display)}'],
    'inherited font family' => ['font family', '.btn{font-family:inherit}'],
    'token variable' => ['undeclared variable', '.a{color:var(--color-ink)}'],
    'variable of the sheet' => ['undeclared variable', '.a{--d:2;transition-delay:calc(var(--d) * 80ms)}'],
    'svg painted with the current colour' => ['markup colour', '<path stroke="currentColor" fill="none"/>'],
    'style with a token' => ['markup colour', '<span style="--i: 2">x</span>'],
    'local font' => ['external resource', "@font-face{src:url('../../fonts/inter-tight-400.woff2')}"],
    'link to WhatsApp' => ['external resource', '<a href="https://wa.me/521234567890">WhatsApp</a>'],
    'treatment Dentissa offers' => ['treatment', "{ name: 'Blanqueamiento Dental' }"],
]);

it('takes every colour, radius, control height and font of the site stylesheets from a token', function () {
    $declared = siteDeclaredVariables();
    $problems = [];

    foreach (siteFiles(SITE_CSS_DIR, '.css') as $path => $css) {
        $code = siteWithoutComments($css);

        foreach ([
            'colour' => siteColourLiterals($code),
            'radius' => siteRadiusLiterals($code),
            'control height' => siteControlHeightLiterals($code),
            'font family' => siteFontFamilyLiterals($code),
            'undeclared variable' => siteUndeclaredVariables($code, $declared),
        ] as $rule => $found) {
            foreach ($found as $literal) {
                $problems[] = "{$path} · {$rule}: {$literal}";
            }
        }
    }

    expect($problems)->toBe([]);
});

it('writes no colour in the markup of the site views', function () {
    $problems = [];

    foreach (siteViews() as $path => $view) {
        foreach (siteMarkupColours($view) as $literal) {
            $problems[] = "{$path}: {$literal}";
        }
    }

    expect($problems)->toBe([]);
});

it('writes no colour and names no discarded treatment in the site scripts', function () {
    $problems = [];

    foreach (siteFiles(SITE_JS_DIR, '.js') as $path => $script) {
        foreach ([...siteColourLiterals($script), ...siteDiscardedTreatments($script)] as $literal) {
            $problems[] = "{$path}: {$literal}";
        }
    }

    expect($problems)->toBe([]);
});

it('loads nothing from another site', function () {
    $problems = [];

    foreach ([...siteFiles(SITE_CSS_DIR, '.css'), ...siteViews()] as $path => $code) {
        foreach (siteExternalResources($code) as $resource) {
            $problems[] = "{$path}: {$resource}";
        }
    }

    expect($problems)->toBe([]);
});

it('ships every font of the site with its licence', function () {
    $missing = [];

    foreach (array_keys(siteFiles(SITE_FONTS_DIR, '.woff2')) as $path) {
        if (! is_file(SITE_STYLES_ROOT.'/'.dirname($path).'/OFL.txt')) {
            $missing[] = $path;
        }
    }

    expect($missing)->toBe([]);
});
