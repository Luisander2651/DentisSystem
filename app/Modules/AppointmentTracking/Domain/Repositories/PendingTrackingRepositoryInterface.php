<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;

/**
 * Spec 020 (CA7, TM1, TM8): the completed appointments that still have no clinical follow-up.
 */
interface PendingTrackingRepositoryInterface
{
    /**
     * One page of them, from the most recent to the oldest (date, time and id), and the total of
     * all; only the ones of `$userId` when it is given (spec 028, CA2, CA7).
     *
     * @return Page<AppointmentEntity>
     */
    public function findCompletedWithoutTracking(?string $userId, PageRequest $request): Page;
}
