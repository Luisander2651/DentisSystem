<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\AppointmentTracking\Domain\Service\PendingTrackingService;

/**
 * Spec 020 (CA7, TM1, TM3): the completed appointments without follow-up, for whoever may
 * register it. The administrator sees the whole clinic, the doctor only their own; the assistant
 * is refused before anything is read. Spec 028 (CA2): one page of ten at a time.
 */
final readonly class GetCompletedWithoutTrackingUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PendingTrackingService $pendingTracking,
    ) {}

    /**
     * @return Page<AppointmentEntity>
     */
    public function execute(int $page = 1): Page
    {
        return $this->pendingTracking->findCompletedWithoutTracking($this->doctorScope(), $page);
    }

    private function doctorScope(): ?string
    {
        if ($this->authorization->can('appointment-tracking.create')) {
            return null;
        }

        if ($this->authorization->can('appointment-tracking.create-own')) {
            return $this->authorization->currentActorId();
        }

        $this->authorization->assertCan('appointment-tracking.create');

        return null;
    }
}
