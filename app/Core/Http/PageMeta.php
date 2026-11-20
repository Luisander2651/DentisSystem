<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Domain\Pagination\Page;

/**
 * Spec 028 (CA1, CA2, CA5): the `meta` of a paged list. `total` counts what matches the search and
 * the filters ("Mostrando X–Y de N"); `counts`, when given, counts the whole list for the header.
 */
final class PageMeta
{
    /**
     * @param  Page<mixed>  $page
     * @param  array<string, int|float|null>|null  $counts
     * @return array{page: int, per_page: int, total: int, last_page: int, from: int|null, to: int|null, counts?: array<string, int|float|null>}
     */
    public static function from(Page $page, ?array $counts = null): array
    {
        $meta = [
            'page' => $page->page,
            'per_page' => $page->size,
            'total' => $page->total,
            'last_page' => $page->lastPage,
            'from' => $page->from,
            'to' => $page->to,
        ];

        if ($counts !== null) {
            $meta['counts'] = $counts;
        }

        return $meta;
    }
}
