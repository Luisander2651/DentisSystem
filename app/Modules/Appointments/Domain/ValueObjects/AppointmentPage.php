<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentPageException;

/**
 * Spec 020 (CA4, CA7, TM8): at most LIMIT appointments and the total of all the ones that match,
 * which is never less than the ones it carries.
 */
final readonly class AppointmentPage
{
    public const LIMIT = 10;

    /**
     * @param  list<AppointmentEntity>  $items
     */
    private function __construct(
        public array $items,
        public int $total,
    ) {}

    /**
     * @param  list<AppointmentEntity>  $items
     */
    public static function create(array $items, int $total): self
    {
        if (count($items) > self::LIMIT) {
            throw AppointmentPageException::tooManyItems(count($items), self::LIMIT);
        }

        if ($total < count($items)) {
            throw AppointmentPageException::totalBelowItems($total, count($items));
        }

        return new self(array_values($items), $total);
    }
}
