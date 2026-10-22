<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\ValueObjects;

use App\Modules\Reports\Domain\Exceptions\MonthlyReportException;

/**
 * Spec 019 (CA5, CA6): one line of a ranking: a treatment with its appointments, or a member of
 * the staff with their appointments and, as the extra figure, the ones they completed. The name
 * is kept as it is; turning it into safe text is the job of each output (CA13).
 */
final readonly class RankedItem
{
    private function __construct(
        public string $name,
        public int $value,
        public ?int $extra,
        public bool $isTop,
    ) {}

    /**
     * @throws MonthlyReportException when the name is empty or a figure is negative
     */
    public static function create(string $name, int $value, ?int $extra = null): self
    {
        if (trim($name) === '') {
            throw MonthlyReportException::emptyName();
        }

        if ($value < 0) {
            throw MonthlyReportException::negativeValue('figure');
        }

        if ($extra !== null && $extra < 0) {
            throw MonthlyReportException::negativeValue('extra figure');
        }

        return new self($name, $value, $extra, false);
    }

    public function asTop(): self
    {
        return new self($this->name, $this->value, $this->extra, true);
    }
}
