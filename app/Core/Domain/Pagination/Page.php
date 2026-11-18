<?php

declare(strict_types=1);

namespace App\Core\Domain\Pagination;

/**
 * Spec 028 (CA1, CA2, CA7, CA9): one page of a list, the total of the items that match and what the
 * page shows ("from"–"to" of the total). A page beyond the last one becomes the last one; with no
 * items there is one page and nothing to show.
 *
 * @template TItem
 */
final readonly class Page
{
    /**
     * @param  list<TItem>  $items
     */
    private function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $size,
        public int $lastPage,
        public ?int $from,
        public ?int $to,
    ) {}

    /**
     * @template T
     *
     * @param  array<int, T>  $items
     * @return self<T>
     */
    public static function create(array $items, int $total, PageRequest $request): self
    {
        $count = count($items);

        if ($total < 0) {
            throw PaginationException::negativeTotal($total);
        }

        if ($count > $request->size) {
            throw PaginationException::tooManyItems($count, $request->size);
        }

        if ($total < $count) {
            throw PaginationException::totalBelowItems($total, $count);
        }

        $page = $request->pageFor($total);
        $from = $count === 0 ? null : $request->offsetFor($total) + 1;

        return new self(
            array_values($items),
            $total,
            $page,
            $request->size,
            PageRequest::lastPageFor($total, $request->size),
            $from,
            $from === null ? null : $from + $count - 1,
        );
    }
}
