<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Core\Domain\Pagination\PaginationException;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 028 (CA7, CA9, CA13; TM1): for any size and any page, a request asks for at most 50 items
 * and keeps its page; for any total, a page is never beyond the last one and its "from" and "to"
 * fit what it carries.
 */

it('asks for the smaller of the size and 50, and keeps any page from 1 on (property)', function () {
    $this->forAll(Eris\Generator\choose(1, 100000), Eris\Generator\choose(1, 100000))->then(function (int $size, int $page) {
        $request = PageRequest::create($page, $size);

        expect($request->size)->toBe(min($size, 50))
            ->and($request->page)->toBe($page);
    });
});

it('refuses any page below 1 (property)', function () {
    $this->forAll(Eris\Generator\choose(-100000, 0))->then(function (int $page) {
        $refused = false;

        try {
            PageRequest::create($page, 12);
        } catch (PaginationException) {
            $refused = true;
        }

        expect($refused)->toBeTrue();
    });
});

it('never goes beyond the last page and its "from" and "to" fit what it carries (property)', function () {
    $this->forAll(
        Eris\Generator\choose(0, 500),
        Eris\Generator\choose(1, 60),
        Eris\Generator\choose(1, 200),
    )->then(function (int $total, int $size, int $asked) {
        $request = PageRequest::create($asked, $size);
        $lastPage = max(1, (int) ceil($total / $request->size));
        $shown = min($asked, $lastPage);
        $count = max(0, min($request->size, $total - ($shown - 1) * $request->size));

        $page = Page::create(array_fill(0, $count, 'item'), $total, $request);

        expect($page->lastPage)->toBe($lastPage)
            ->and($page->page)->toBe($shown)
            ->and($page->page)->toBeLessThanOrEqual($page->lastPage)
            ->and($page->total)->toBeGreaterThanOrEqual(count($page->items))
            ->and(count($page->items))->toBeLessThanOrEqual($request->size)
            ->and($request->offsetFor($total))->toBe(($shown - 1) * $request->size);

        if ($count === 0) {
            expect($page->from)->toBeNull()->and($page->to)->toBeNull();

            return;
        }

        expect($page->from)->toBe(($shown - 1) * $request->size + 1)
            ->and($page->to)->toBe($page->from + $count - 1)
            ->and($page->to)->toBeLessThanOrEqual($total);
    });
});
