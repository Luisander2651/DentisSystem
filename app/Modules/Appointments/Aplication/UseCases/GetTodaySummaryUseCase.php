<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\Support\DoctorScope;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\TodayAgenda;

/**
 * Spec 020 (CA1, CA2, CA3, TM1): the agenda of today of the dashboard, the whole clinic or only
 * the doctor's own, at the time of the clinic.
 */
final readonly class GetTodaySummaryUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): TodayAgenda
    {
        $userId = DoctorScope::forActor($this->authorization);
        $now = now()->toDateTimeImmutable();

        $appointments = $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: AppointmentDate::fromString($now->format('Y-m-d')),
            userId: $userId,
        );

        return TodayAgenda::fromAppointments(array_values($appointments), $now);
    }
}
