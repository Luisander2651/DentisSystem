<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\GetAllAppointmentsByStatusAndDateDTO;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class GetAllApointmentsByStatusAndDateUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(GetAllAppointmentsByStatusAndDateDTO $getAllAppointmentsByStatusAndDateDTO): array
    {
        $this->authorization->assertCan('appointments.view');

        $status = null;
        $date = null;

        if ($getAllAppointmentsByStatusAndDateDTO->status !== null) {
            $status = AppointmentStatus::fromString($getAllAppointmentsByStatusAndDateDTO->status);
        }

        if ($getAllAppointmentsByStatusAndDateDTO->date !== null) {
            $date = AppointmentDate::fromString($getAllAppointmentsByStatusAndDateDTO->date);
        }

        $appointments = $this->appointmentsService->findByStatusAndDate(
            status: $status,
            date: $date,
        );

        if (empty($appointments)) {
            throw AppointmentException::notFoundByFilters();
        }

        return $appointments;
    }
}