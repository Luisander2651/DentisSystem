<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;

final readonly class GetTodayAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): array
    {
        $userId = $this->doctorScope();

        return $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: AppointmentDate::fromString(now()->toDateString()),
            userId: $userId,
        );
    }

    /**
     * Spec 018: who may see the whole agenda gets no narrowing; the doctor only ever gets their
     * own appointments, whatever the request asks for. Anyone else is refused.
     */
    private function doctorScope(): ?AppointmentUserId
    {
        if ($this->authorization->can('appointments.view')) {
            return null;
        }

        if ($this->authorization->can('appointments.view-own')) {
            return new AppointmentUserId($this->authorization->currentActorId());
        }

        $this->authorization->assertCan('appointments.view');

        return null;
    }
}
