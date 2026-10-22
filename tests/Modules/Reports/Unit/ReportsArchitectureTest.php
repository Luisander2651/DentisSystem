<?php

declare(strict_types=1);

/*
 * Spec 019, P3 and ADR 0006: the Domain of Reports knows neither Eloquent, nor facades, nor the
 * Infrastructure of any module, nor the spreadsheet library; Application does not know the
 * library or Eloquent either.
 */

/**
 * The `use` lines of every PHP file under a folder of the Reports module.
 *
 * @return array<string, list<string>>
 */
function reportsImports(string $layer): array
{
    $root = dirname(__DIR__, 4).'/app/Modules/Reports/'.$layer;
    $imports = [];

    if (! is_dir($root)) {
        return $imports;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/^use\s+([^;]+);/m', (string) file_get_contents($file->getPathname()), $matches);
        $imports[substr($file->getPathname(), strlen($root) + 1)] = $matches[1];
    }

    return $imports;
}

/**
 * @param  array<string, list<string>>  $imports
 * @return list<string>
 */
function reportsForbiddenImports(array $imports, string $pattern): array
{
    $found = [];

    foreach ($imports as $file => $uses) {
        foreach ($uses as $use) {
            if (preg_match($pattern, $use) === 1) {
                $found[] = "$file: $use";
            }
        }
    }

    return $found;
}

it('has a Domain with classes', function () {
    expect(is_dir(dirname(__DIR__, 4).'/app/Modules/Reports/Domain'))->toBeTrue()
        ->and(reportsImports('Domain'))->not->toBeEmpty();
});

it('keeps the Domain free of Eloquent, facades, Infrastructure and the spreadsheet library', function () {
    expect(reportsForbiddenImports(reportsImports('Domain'), '/^(App\\\\Modules\\\\[^\\\\]+\\\\Infrastructure|Illuminate\\\\Support\\\\Facades|Illuminate\\\\Database\\\\Eloquent|OpenSpout)/'))->toBe([]);
});

it('keeps Application free of Eloquent and the spreadsheet library', function () {
    expect(reportsForbiddenImports(reportsImports('Application'), '/^(Illuminate\\\\Database\\\\Eloquent|OpenSpout|App\\\\Modules\\\\[^\\\\]+\\\\Infrastructure)/'))->toBe([]);
});
