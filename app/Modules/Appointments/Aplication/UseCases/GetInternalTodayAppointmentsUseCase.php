<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;

/**
 * Every appointment of the clinic day, for every doctor, for the attendance microservice. Like
 * GetInternalAppointmentByIdUseCase, the caller is authenticated by the `service.key` middleware.
 */
final readonly class GetInternalTodayAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    /**
     * @return list<AppointmentEntity>
     */
    public function execute(): array
    {
        return $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: AppointmentDate::fromString(now()->toDateString()),
            userId: null,
        );
    }
}
