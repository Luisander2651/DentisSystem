<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Core\Http\PageMeta;

/*
 * Spec 028 (CA1, CA2, CA5): the `meta` of a paged list says the page, its size, the total that
 * matches, the last page and what the page shows; the counters of the whole list go apart.
 */

it('describes a page with its total and what it shows', function () {
    $page = Page::create(range(13, 24), 30, PageRequest::create(2, 12));

    expect(PageMeta::from($page))->toBe([
        'page' => 2,
        'per_page' => 12,
        'total' => 30,
        'last_page' => 3,
        'from' => 13,
        'to' => 24,
    ]);
});

it('adds the counters of the whole list when there are any', function () {
    $page = Page::create([], 0, PageRequest::create(1, 12));

    expect(PageMeta::from($page, ['total' => 30, 'active' => 29, 'inactive' => 1]))->toBe([
        'page' => 1,
        'per_page' => 12,
        'total' => 0,
        'last_page' => 1,
        'from' => null,
        'to' => null,
        'counts' => ['total' => 30, 'active' => 29, 'inactive' => 1],
    ]);
});
