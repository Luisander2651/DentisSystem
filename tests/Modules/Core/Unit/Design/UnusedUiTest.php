<?php

declare(strict_types=1);

/*
 * Spec 016, CA17 (DS13): the interface carries no piece that nobody can reach. The three the
 * design system found are gone, and so is the old-markup branch that resources/js/ui/dialog.js
 * only needs while the dialogs are being migrated.
 */

const UNUSED_UI_ROOT = __DIR__.'/../../../../..';

/**
 * @return list<string>
 */
function unusedUiFilesContaining(string $needle): array
{
    $found = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UNUSED_UI_ROOT.'/resources', FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile() && str_contains((string) file_get_contents($file->getPathname()), $needle)) {
            $found[] = str_replace('\\', '/', substr($file->getPathname(), strlen(UNUSED_UI_ROOT) + 1));
        }
    }

    return $found;
}

it('finds the files that contain a text (positive control)', function () {
    expect(unusedUiFilesContaining('data-create-appointment-open'))->toContain('resources/views/pages/agenda/index.blade.php');
});

it('has no x-ui.table component, which no view used', function () {
    expect(is_file(UNUSED_UI_ROOT.'/resources/views/components/ui/table.blade.php'))->toBeFalse()
        ->and(unusedUiFilesContaining('<x-ui.table'))->toBe([]);
})->skip('016: pendiente de T072');

it('has no welcome view, which no route served', function () {
    expect(is_file(UNUSED_UI_ROOT.'/resources/views/welcome.blade.php'))->toBeFalse()
        ->and(unusedUiFilesContaining("view('welcome')"))->toBe([]);
})->skip('016: pendiente de T072');

it('has no "view and edit" branch in the appointment card, which was never painted', function () {
    expect(unusedUiFilesContaining('data-edit-appointment-trigger'))->toBe([]);
})->skip('016: pendiente de T065');

it('has no old-markup branch left in the dialog module', function () {
    $module = UNUSED_UI_ROOT.'/resources/js/ui/dialog.js';

    expect(is_file($module))->toBeTrue()
        ->and(stripos((string) file_get_contents($module), 'legacy'))->toBeFalse();
})->skip('016: pendiente de T073');
