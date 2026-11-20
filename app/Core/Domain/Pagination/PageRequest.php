<?php

declare(strict_types=1);

namespace App\Core\Domain\Pagination;

/**
 * Spec 028 (CA9, CA13; TM1, TM4): the page a list is asked for and how many items it holds. The
 * size never goes above MAX_SIZE, whatever the caller asks, so no request reads a whole list at once.
 */
final readonly class PageRequest
{
    public const MAX_SIZE = 50;

    private function __construct(
        public int $page,
        public int $size,
    ) {}

    public static function create(int $page, int $size): self
    {
        if ($page < 1) {
            throw PaginationException::invalidPage($page);
        }

        if ($size < 1) {
            throw PaginationException::invalidSize($size);
        }

        return new self($page, min($size, self::MAX_SIZE));
    }

    /**
     * The page actually shown for a total: the one asked for, or the last one when it goes beyond.
     */
    public function pageFor(int $total): int
    {
        return min($this->page, self::lastPageFor($total, $this->size));
    }

    /**
     * Where the reading starts for a total, so a page far beyond the last one never reads past it.
     */
    public function offsetFor(int $total): int
    {
        return ($this->pageFor($total) - 1) * $this->size;
    }

    public static function lastPageFor(int $total, int $size): int
    {
        return max(1, intdiv(max(0, $total) + $size - 1, $size));
    }
}
