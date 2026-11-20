<?php

declare(strict_types=1);

namespace App\Core\Domain\Pagination;

use DomainException;

final class PaginationException extends DomainException
{
    public static function invalidPage(int $page): self
    {
        return new self(sprintf('A page starts at 1, <%d> given.', $page));
    }

    public static function invalidSize(int $size): self
    {
        return new self(sprintf('A page holds at least 1 item, <%d> given.', $size));
    }

    public static function negativeTotal(int $total): self
    {
        return new self(sprintf('A total cannot be negative, <%d> given.', $total));
    }

    public static function tooManyItems(int $count, int $size): self
    {
        return new self(sprintf('A page carries at most %d items, %d given.', $size, $count));
    }

    public static function totalBelowItems(int $total, int $count): self
    {
        return new self(sprintf('The total <%d> is less than the %d items of the page.', $total, $count));
    }
}
