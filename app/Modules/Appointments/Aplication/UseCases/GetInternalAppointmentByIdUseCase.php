<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;

/**
 * The attendance microservice validating a QR. There is no actor to pass to assertCan(): the
 * caller is the service itself, authenticated by the `service.key` middleware of the route.
 */
final readonly class GetInternalAppointmentByIdUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(GetAppointmentByIdDTO $getAppointmentByIdDTO): ?AppointmentEntity
    {
        $appointmentId = new AppointmentId($getAppointmentByIdDTO->appointmentId);

        return $this->appointmentsService->findById($appointmentId);
    }
}
