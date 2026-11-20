<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Service;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;

/**
 * Spec 028 (CA2, A2, D12): the completed appointments still without a clinical follow-up, ten per
 * page, the whole clinic or only the ones of a doctor.
 */
final readonly class PendingTrackingService
{
    public const PAGE_SIZE = 10;

    public function __construct(
        private PendingTrackingRepositoryInterface $repository,
    ) {}

    /**
     * @return Page<AppointmentEntity>
     */
    public function findCompletedWithoutTracking(?string $userId, int $page = 1): Page
    {
        return $this->repository->findCompletedWithoutTracking($userId, PageRequest::create($page, self::PAGE_SIZE));
    }
}
