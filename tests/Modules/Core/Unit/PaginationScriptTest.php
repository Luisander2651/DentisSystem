<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/*
 * Spec 028 (CA3, CA6, CA8, CA9, CA11): the pagination control of resources/js/ui/pagination.js,
 * run in Node by render-pagination.mjs. It shows the first and the last page, the current one and
 * its neighbours with "…" in the gaps; "Anterior" and "Siguiente" cannot be used at the ends; with
 * one page it shows nothing; and the address keeps the page, the search and the filter.
 */

/**
 * @param  array<string, mixed>  $arguments
 * @return array<mixed>
 */
function runPaginationScript(string $scenario, array $arguments = []): array
{
    $process = new Process(['node', __DIR__.'/render-pagination.mjs', $scenario, json_encode($arguments, JSON_THROW_ON_ERROR)]);
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array{page: int, per_page: int, total: int, last_page: int, from: int|null, to: int|null}
 */
function paginationMeta(int $page, int $lastPage, int $perPage = 12, ?int $total = null): array
{
    $total ??= $lastPage * $perPage;
    $from = ($page - 1) * $perPage + 1;

    return ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => $lastPage, 'from' => $from, 'to' => min($total, $from + $perPage - 1)];
}

function paginationHtml(int $page, int $lastPage, int $perPage = 12, ?int $total = null, string $noun = 'pacientes'): string
{
    return runPaginationScript('markup', ['meta' => paginationMeta($page, $lastPage, $perPage, $total), 'noun' => $noun])['html'];
}

/**
 * The visible labels of the page buttons and gaps, in order ("…" for a gap).
 *
 * @return list<string>
 */
function paginationNumbers(string $html): array
{
    preg_match('/<ul[^>]*>(.*?)<\/ul>/s', $html, $list);
    preg_match_all('/<(?:a|span)\b[^>]*>([^<]*)<\/(?:a|span)>/', $list[1] ?? '', $items);

    return array_map('trim', $items[1]);
}

/**
 * The labels of a window as seen from 768 px (`desktop`) or below it (`mobile`), "…" for a gap.
 *
 * @param  list<array<string, mixed>>  $window
 * @return list<string>
 */
function paginationWindowAt(array $window, string $width): array
{
    $seen = array_filter($window, fn (array $entry): bool => $width === 'mobile'
        ? ! ($entry['near'] ?? false)
        : ! ($entry['mobile'] ?? false));

    return array_values(array_map(fn (array $entry): string => $entry['type'] === 'gap' ? '…' : (string) $entry['page'], $seen));
}

it('shows the first, the last, the current page and its neighbours, with "…" in the gaps', function (int $page, int $lastPage, array $expected) {
    $window = runPaginationScript('window', ['page' => $page, 'lastPage' => $lastPage]);

    expect(paginationWindowAt($window, 'desktop'))->toBe($expected);
})->with([
    'page 2 of 8' => [2, 8, ['1', '2', '3', '4', '…', '8']],
    'page 1 of 3' => [1, 3, ['1', '2', '3']],
    'page 8 of 8' => [8, 8, ['1', '…', '6', '7', '8']],
    'page 5 of 10' => [5, 10, ['1', '…', '3', '4', '5', '6', '7', '…', '10']],
]);

/*
 * Review R1: below 768 px the neighbours two pages away hide, and a jump they leave next to the
 * first or the last page gets its own "…", seen only there.
 */
it('keeps a "…" in every gap below 768 px too', function (int $page, int $lastPage, array $desktop, array $mobile) {
    $window = runPaginationScript('window', ['page' => $page, 'lastPage' => $lastPage]);

    expect(paginationWindowAt($window, 'desktop'))->toBe($desktop)
        ->and(paginationWindowAt($window, 'mobile'))->toBe($mobile);
})->with([
    'page 4 of 8' => [4, 8, ['1', '2', '3', '4', '5', '6', '…', '8'], ['1', '…', '3', '4', '5', '…', '8']],
    'page 5 of 8' => [5, 8, ['1', '…', '3', '4', '5', '6', '7', '8'], ['1', '…', '4', '5', '6', '…', '8']],
    'page 2 of 8' => [2, 8, ['1', '2', '3', '4', '…', '8'], ['1', '2', '3', '…', '8']],
    'page 3 of 5' => [3, 5, ['1', '2', '3', '4', '5'], ['1', '2', '3', '4', '5']],
]);

it('shows the gaps of the narrow window only below 768 px', function () {
    $html = paginationHtml(4, 8);

    expect($html)->toMatch('/<li class="[^"]*\bmd:hidden\b[^"]*"><span\b[^>]*aria-hidden="true"[^>]*>…<\/span><\/li>/')
        ->and(substr_count($html, '>…</span>'))->toBe(2);
});

it('marks the neighbours two pages away so they hide below 768 px', function () {
    $window = runPaginationScript('window', ['page' => 2, 'lastPage' => 8]);
    $near = array_values(array_filter($window, fn (array $entry): bool => $entry['near'] ?? false));

    expect(array_column($near, 'page'))->toBe([4]);
});

it('builds the control of page 2 of 8 with its summary and its names', function () {
    $html = paginationHtml(2, 8, total: 87);

    expect(paginationNumbers($html))->toBe(['1', '2', '3', '4', '…', '8'])
        ->and(strip_tags($html))->toContain('Mostrando 13–24 de 87 pacientes')
        ->and($html)->toMatch('/<a\b[^>]*aria-current="page"[^>]*aria-label="Página 2, actual"/')
        ->toContain('aria-label="Página 3"')
        ->toContain('aria-label="Página 8, última"')
        ->toMatch('/<a\b[^>]*href="\?"[^>]*data-pagination-page="1"[^>]*aria-label="Página anterior"[^>]*>.*Anterior<\/a>/s')
        ->toMatch('/<a\b[^>]*href="\?pagina=3"[^>]*aria-label="Página siguiente"[^>]*>.*Siguiente.*<\/a>/s')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->not->toContain('aria-disabled');
});

it('leaves "Anterior" unusable on the first page and "Siguiente" on the last one', function () {
    $first = paginationHtml(1, 3, perPage: 10, total: 27, noun: 'citas');
    $last = paginationHtml(8, 8, total: 87);

    // Review R2: a link without href keeps its role and its name, which a plain span cannot have.
    expect($first)->toMatch('/<a\b(?![^>]*\bhref=)(?![^>]*data-pagination-page)[^>]*role="link"[^>]*aria-disabled="true"[^>]*aria-label="Página anterior, no disponible"[^>]*>/')
        ->not->toMatch('/<a\b[^>]*href="[^"]*"[^>]*aria-label="Página anterior/')
        ->not->toMatch('/<span\b[^>]*aria-disabled/')
        ->and(strip_tags($first))->toContain('Mostrando 1–10 de 27 citas')
        ->and(paginationNumbers($first))->toBe(['1', '2', '3'])
        ->and($last)->toMatch('/<a\b(?![^>]*\bhref=)(?![^>]*data-pagination-page)[^>]*role="link"[^>]*aria-disabled="true"[^>]*aria-label="Página siguiente, no disponible"[^>]*>/')
        ->not->toMatch('/<a\b[^>]*href="[^"]*"[^>]*aria-label="Página siguiente/');
});

it('draws the current page bold and the others semibold (review R3)', function () {
    $html = paginationHtml(2, 8);
    preg_match('/<a\b[^>]*aria-current="page"[^>]*>/', $html, $current);
    preg_match('/<a\b[^>]*aria-label="Página 3"[^>]*>/', $html, $other);

    expect($current[0])->toContain('font-bold')->not->toContain('font-semibold')
        ->and($other[0])->toContain('font-semibold')->not->toContain('font-bold');
});

it('hides the gap from screen readers and keeps the unusable buttons out of the focus order', function () {
    $html = paginationHtml(1, 8);

    expect($html)->toMatch('/<span\b[^>]*aria-hidden="true"[^>]*>…<\/span>/')
        ->not->toMatch('/<a\b[^>]*aria-disabled="true"[^>]*tabindex=/');
});

it('builds nothing when everything fits in one page or there is nothing', function (array $meta) {
    expect(runPaginationScript('markup', ['meta' => $meta, 'noun' => 'pacientes'])['html'])->toBe('');
})->with([
    'one page' => [['page' => 1, 'per_page' => 12, 'total' => 5, 'last_page' => 1, 'from' => 1, 'to' => 5]],
    'nothing' => [['page' => 1, 'per_page' => 12, 'total' => 0, 'last_page' => 1, 'from' => null, 'to' => null]],
]);

it('reads the page, the search and the filter from the address, with page 1 for anything else', function (string $search, array $expected) {
    expect(runPaginationScript('read', ['search' => $search]))->toBe($expected);
})->with([
    'nothing' => ['', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'page 2 with a search and a filter' => ['?pagina=2&busqueda=Valeria&estado=active', ['pagina' => 2, 'busqueda' => 'Valeria', 'estado' => 'active']],
    'a page that is text' => ['?pagina=abc', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'page 0' => ['?pagina=0', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'a negative page' => ['?pagina=-3', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'a page with decimals' => ['?pagina=2.5', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'the search focus flag of Expedientes' => ['?buscar=1', ['pagina' => 1, 'busqueda' => '', 'estado' => '']],
    'a search longer than the server takes (review R4)' => ['?busqueda='.str_repeat('a', 150), ['pagina' => 1, 'busqueda' => str_repeat('a', 100), 'estado' => '']],
]);

it('writes only what differs from the first page with no search', function (array $state, string $expected) {
    expect(runPaginationScript('query', ['state' => $state])['query'])->toBe($expected);
})->with([
    'the first page' => [['pagina' => 1, 'busqueda' => '', 'estado' => ''], ''],
    'page 3' => [['pagina' => 3, 'busqueda' => '', 'estado' => ''], '?pagina=3'],
    'a search and a filter' => [['pagina' => 2, 'busqueda' => 'Ana María', 'estado' => 'inactive'], '?pagina=2&busqueda=Ana+Mar%C3%ADa&estado=inactive'],
]);
