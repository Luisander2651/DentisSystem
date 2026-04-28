<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;

final readonly class GetAppointmentByIdUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(GetAppointmentByIdDTO $getAppointmentByIdDTO): ?array
    {
        $appointmentId = new AppointmentId($getAppointmentByIdDTO->appointmentId);
        return $this->appointmentsService->findById($appointmentId);
    }
}