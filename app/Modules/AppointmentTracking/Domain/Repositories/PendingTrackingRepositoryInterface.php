<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;

/**
 * Spec 020 (CA7, TM1, TM8): the completed appointments that still have no clinical follow-up.
 */
interface PendingTrackingRepositoryInterface
{
    /**
     * At most `$limit` of them, from the most recent to the oldest, and the total of all; only the
     * ones of `$userId` when it is given.
     *
     * @return array{items: list<AppointmentEntity>, total: int}
     */
    public function findCompletedWithoutTracking(?string $userId, int $limit): array;
}
