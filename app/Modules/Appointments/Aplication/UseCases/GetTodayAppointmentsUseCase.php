<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\Support\DoctorScope;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;

final readonly class GetTodayAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): array
    {
        $userId = DoctorScope::forActor($this->authorization);

        return $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: AppointmentDate::fromString(now()->toDateString()),
            userId: $userId,
        );
    }
}
