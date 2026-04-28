<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\DeleteAppointmentDTO;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;

final readonly class DeleteAppointmentUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(DeleteAppointmentDTO $deleteAppointmentDTO): void
    {
        $appointmentId = new AppointmentId($deleteAppointmentDTO->appointmentId);
        $this->appointmentsService->deleteAppointment($appointmentId);
    }
}