<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;

/**
 * Spec 024: the QR code of an appointment holds only its id, for the attendance service to read
 * at the door. Whoever may see the detail of the appointment may get it.
 */
final readonly class GetAppointmentQrUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private QrCodeGeneratorInterface $qrCodeGenerator,
    ) {}

    /**
     * The SVG of the QR code, or null when the appointment does not exist.
     */
    public function execute(GetAppointmentByIdDTO $getAppointmentByIdDTO): ?string
    {
        $this->authorization->assertCan('appointments.view-detail');

        $appointmentId = new AppointmentId($getAppointmentByIdDTO->appointmentId);

        if ($this->appointmentsService->findById($appointmentId) === null) {
            return null;
        }

        return $this->qrCodeGenerator->svg($appointmentId->value);
    }
}
