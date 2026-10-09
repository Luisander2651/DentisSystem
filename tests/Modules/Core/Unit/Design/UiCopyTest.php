<?php

declare(strict_types=1);

/*
 * Spec 016, CA16 and CA30: the interface is written in correct Spanish and without the
 * jargon of the trade. The test reads only what a person sees - text between tags, the text
 * attributes, and the phrases a script builds - and fails when a migrated file (one that is
 * no longer in pending-files.php) shows a word of forbidden-words.php.
 *
 * It is static: a message that arrives from the API is not here. resources/js/ui/status.js
 * shows its own Spanish text for those, and `npm run test:ui` checks what gets announced (A63).
 */

const UI_COPY_ROOT = __DIR__.'/../../../../..';

const UI_COPY_SILENT_ATTRIBUTES = 'class|id|href|src|srcset|action|method|name|type|for|value|style|d|viewBox|fill|stroke|stroke-[\w-]+|xmlns|role|rel|target|autocomplete|inputmode|pattern|loading|width|height|points|cx|cy|r|rx|ry|x|y|x1|x2|y1|y2|data-[\w-]+|aria-(?!label\b)[\w-]+|modal-id|as|variant|size|lang|charset|content|http-equiv|property';

/**
 * What a person can read in a view or in a script.
 *
 * @return list<string>
 */
function uiCopyVisibleTexts(string $file, string $content): array
{
    $texts = [];
    $stripTags = fn (string $html): string => (string) preg_replace('/<[^>]*>/', "\n", $html);
    $textAttributes = function (string $html) use (&$texts): void {
        preg_match_all('/(?<![\w:@-])([\w-]+)="([^"]*)"/', $html, $attributes, PREG_SET_ORDER);

        foreach ($attributes as [, $name, $value]) {
            if (! preg_match('/^(?:'.UI_COPY_SILENT_ATTRIBUTES.')$/', $name)) {
                $texts[] = $value;
            }
        }
    };

    if (str_ends_with($file, '.blade.php')) {
        $content = (string) preg_replace(['/\{\{--.*?--\}\}/s', '/<style\b.*?<\/style>/s'], ' ', $content);

        // Scripts written inside a view are read as scripts.
        $content = (string) preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/s', function (array $script) use (&$texts): string {
            array_push($texts, ...uiCopyVisibleTexts('inline.js', $script[1]));

            return ' ';
        }, $content);

        $textAttributes($content);

        // Phrases in PHP strings: default props, ternaries inside an echo, section titles.
        preg_match_all("/'((?:[^'\\\\\\n]|\\\\.)*)'/", $content, $strings);
        array_push($texts, ...array_filter($strings[1], fn (string $string): bool => (bool) preg_match('/\s|^\p{Lu}/u', $string)));

        // The code of a @php block is not text; its phrases were read just above.
        $withoutBlade = (string) preg_replace(['/@php\b.*?@endphp/s', '/\{\{.*?\}\}/s', '/\{!!.*?!!\}/s', '/@[a-zA-Z]+\s*\((?:[^()]|\([^()]*\))*\)/', '/@[a-zA-Z]+/'], ' ', $content);
        array_push($texts, ...explode("\n", $stripTags($withoutBlade)));
    } else {
        $content = (string) preg_replace(['/\/\*.*?\*\//s', '/^\s*\/\/.*$/m'], ' ', $content);
        preg_match_all('/\'((?:[^\'\\\\\n]|\\\\.)*)\'|"((?:[^"\\\\\n]|\\\\.)*)"|`((?:[^`\\\\]|\\\\.)*)`/', $content, $literals, PREG_SET_ORDER);

        foreach ($literals as $literal) {
            $value = (string) preg_replace('/\$\{[^}]*\}/', ' ', $literal[1] ?: ($literal[2] ?? '') ?: ($literal[3] ?? ''));
            $textAttributes($value);
            $text = trim($stripTags($value));

            // A lone lowercase word is a key or a selector, not a phrase.
            if (preg_match('/\s/u', $text) || preg_match('/^\p{Lu}/u', $text)) {
                $texts[] = $text;
            }
        }
    }

    return array_values(array_filter(array_map(trim(...), $texts), fn (string $text): bool => (bool) preg_match('/\p{L}{2,}/u', $text)));
}

/**
 * The forbidden words a set of visible texts shows, each with its correction when it has one.
 *
 * @param  list<string>  $texts
 * @return list<string>
 */
function uiCopyForbidden(array $texts): array
{
    $words = require __DIR__.'/forbidden-words.php';
    $found = [];
    $pattern = fn (string $word, string $flags): string => '/(?<![\p{L}\p{N}_\/.#-])'.preg_quote($word, '/').'(?![\p{L}\p{N}_\/-])/'.$flags;

    foreach ($texts as $text) {
        foreach ($words['spelling'] as $wrong => $right) {
            if (preg_match($pattern($wrong, 'iu'), $text)) {
                $found[] = "$wrong → $right";
            }
        }

        foreach ($words['technical'] as $term) {
            if (preg_match($pattern($term, $term === strtoupper($term) ? 'u' : 'iu'), $text)) {
                $found[] = $term;
            }
        }
    }

    return array_values(array_unique($found));
}

/**
 * @return list<string>
 */
function uiCopyMigratedFiles(): array
{
    $files = [];

    foreach (['resources/views' => '.blade.php', 'resources/js' => '.js'] as $directory => $suffix) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UI_COPY_ROOT.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $files[] = $directory.str_replace('\\', '/', substr($file->getPathname(), strlen(UI_COPY_ROOT.'/'.$directory)));
            }
        }
    }

    sort($files);

    return array_values(array_diff($files, require __DIR__.'/pending-files.php'));
}

it('catches a forbidden word wherever a person reads it (positive controls)', function (string $file, string $content, string $expected) {
    expect(uiCopyForbidden(uiCopyVisibleTexts($file, $content)))->toContain($expected);
})->with([
    'text between tags' => ['a.blade.php', '<h2 class="text-ink">Gestion de contenido</h2>', 'gestion → gestión'],
    'capitalised word' => ['a.blade.php', '<p>ELIMINACION</p>', 'eliminacion → eliminación'],
    'section title' => ['a.blade.php', "@section('title', 'Iniciar Sesion - Dentissa')", 'sesion → sesión'],
    'placeholder' => ['a.blade.php', '<input type="text" placeholder="Telefono de contacto">', 'telefono → teléfono'],
    'aria-label' => ['a.blade.php', '<button aria-label="Abrir menu"></button>', 'menu → menú'],
    'component prop' => ['a.blade.php', '<x-ui.confirm-delete-modal message-prefix="Esta segura que desea eliminar" />', 'esta segura → está segura'],
    'default of a prop' => ['a.blade.php', "@props(['title' => 'Confirmar eliminacion'])", 'eliminacion → eliminación'],
    'phrase inside an echo' => ['a.blade.php', "<p>{{ \$canEdit ? 'editar la informacion' : 'consultar' }}</p>", 'informacion → información'],
    'script inside a view' => ['a.blade.php', "<script>status.textContent = 'No se pudo confirmar el logout en API.';</script>", 'API'],
    'message of a script' => ['a.js', "showModalError('No se encontro el ID de la imagen a editar.');", 'encontro → encontró'],
    'technical term in a script' => ['a.js', "showModalError('No se encontro el ID de la imagen a editar.');", 'ID'],
    'markup built by a script' => ['a.js', "html.push('<p class=\"mt-1\">Imagen cargada desde la base de datos.</p>');", 'base de datos'],
    'attribute built by a script' => ['a.js', "html.push('<button aria-label=\"Editar promocion\" data-promotions-edit></button>');", 'promocion → promoción'],
    'template literal' => ['a.js', 'label.textContent = `Ultima visita: ${date}`;', 'ultima → última'],
    'jargon in a view' => ['a.blade.php', '<p>Usa esta pantalla con las rutas admin de la API.</p>', 'rutas admin'],
    'backend' => ['a.blade.php', '<p>El backend asigna visible por defecto.</p>', 'backend'],
]);

it('ignores what nobody reads (negative controls)', function (string $file, string $content) {
    expect(uiCopyForbidden(uiCopyVisibleTexts($file, $content)))->toBe([]);
})->with([
    'correct Spanish' => ['a.blade.php', '<h2>Gestión de contenido</h2><p>¿Está segura de que desea eliminar la promoción? Esta acción no se puede deshacer.</p>'],
    'routes, hooks and identifiers of a view' => ['a.blade.php', '<a href="/galeria" id="menu" class="menu" data-content-tab="galeria" data-sidebar-menu>{{ route(\'galeria\') }}</a><div id="mobile-menu"></div>'],
    'comment of a view' => ['a.blade.php', '{{-- Seccion de gestion --}}<p>Contenido</p>'],
    'keys and selectors of a script' => ['a.js', "activateTab('galeria'); document.querySelector('#mobile-menu [data-sidebar-menu]'); fetch('/api/v1/gallery?status=visible');"],
    'comment of a script' => ['a.js', "// la API devuelve el ID\nvar id = row.id;"],
    'identifiers of a script' => ['a.js', 'const token = getCookie(name); var apiUrl = base + id;'],
    'lowercase id inside a word' => ['a.blade.php', '<p>La validez del código es de un día.</p>'],
]);

it('shows no misspelt word and no jargon in any migrated view or script', function () {
    $found = [];

    foreach (uiCopyMigratedFiles() as $file) {
        $forbidden = uiCopyForbidden(uiCopyVisibleTexts($file, (string) file_get_contents(UI_COPY_ROOT.'/'.$file)));

        if ($forbidden !== []) {
            $found[$file] = $forbidden;
        }
    }

    expect($found)->toBe([]);
});
