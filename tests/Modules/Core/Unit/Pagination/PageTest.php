<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Core\Domain\Pagination\PaginationException;

/*
 * Spec 028 (CA9, CA13; TM1, TM4): a page request never asks for more than 50 items, and a page
 * adjusts a page beyond the last one to the last one and says what it shows.
 */

it('keeps a size of 50 and lowers anything above it to 50', function (int $asked, int $kept) {
    expect(PageRequest::create(1, $asked)->size)->toBe($kept);
})->with([
    'the maximum' => [50, 50],
    'one above the maximum' => [51, 50],
    'a whole list at once' => [10000, 50],
    'one item' => [1, 1],
]);

it('refuses a page or a size below one', function (int $page, int $size) {
    PageRequest::create($page, $size);
})->with([
    'page 0' => [0, 12],
    'a negative page' => [-3, 12],
    'size 0' => [1, 0],
    'a negative size' => [1, -1],
])->throws(PaginationException::class);

it('keeps the page that was asked for', function () {
    expect(PageRequest::create(3, 12)->page)->toBe(3);
});

it('says from where it starts reading for a total, with the page adjusted to the last one', function () {
    expect(PageRequest::create(3, 12)->offsetFor(30))->toBe(24)
        ->and(PageRequest::create(999, 12)->offsetFor(30))->toBe(24)
        ->and(PageRequest::create(999999999, 12)->offsetFor(30))->toBe(24)
        ->and(PageRequest::create(2, 12)->offsetFor(0))->toBe(0);
});

it('describes the second page of 30 items in pages of 12', function () {
    $page = Page::create(range(13, 24), 30, PageRequest::create(2, 12));

    expect($page->items)->toBe(range(13, 24))
        ->and($page->total)->toBe(30)
        ->and($page->page)->toBe(2)
        ->and($page->size)->toBe(12)
        ->and($page->lastPage)->toBe(3)
        ->and($page->from)->toBe(13)
        ->and($page->to)->toBe(24);
});

it('adjusts a page beyond the last one to the last one', function () {
    $page = Page::create(range(25, 30), 30, PageRequest::create(999, 12));

    expect($page->page)->toBe(3)
        ->and($page->from)->toBe(25)
        ->and($page->to)->toBe(30);
});

it('has one page and nothing to show when the total is 0', function () {
    $page = Page::create([], 0, PageRequest::create(4, 12));

    expect($page->page)->toBe(1)
        ->and($page->lastPage)->toBe(1)
        ->and($page->from)->toBeNull()
        ->and($page->to)->toBeNull();
});

it('refuses more items than the size or a total under its items', function (int $count, int $total) {
    Page::create(array_fill(0, $count, 'item'), $total, PageRequest::create(1, 10));
})->with([
    'more items than the size' => [11, 40],
    'a total under its items' => [5, 4],
    'a negative total' => [0, -1],
])->throws(PaginationException::class);
