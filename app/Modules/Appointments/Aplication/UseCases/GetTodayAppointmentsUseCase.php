<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class GetTodayAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): array
    {
        $this->authorization->assertCan('appointments.view');

        return $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: AppointmentDate::fromString(now()->toDateString()),
        );
    }
}