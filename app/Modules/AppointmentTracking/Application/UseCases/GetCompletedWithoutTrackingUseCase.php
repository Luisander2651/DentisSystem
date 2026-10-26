<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;

/**
 * Spec 020 (CA7, TM1, TM3): the completed appointments without follow-up, for whoever may
 * register it. The administrator sees the whole clinic, the doctor only their own; the assistant
 * is refused before anything is read.
 */
final readonly class GetCompletedWithoutTrackingUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PendingTrackingRepositoryInterface $repository,
    ) {}

    /**
     * @return array{items: list<AppointmentEntity>, total: int}
     */
    public function execute(): array
    {
        return $this->repository->findCompletedWithoutTracking($this->doctorScope(), AppointmentPage::LIMIT);
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
