<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;

/**
 * Spec 025 (CA7, CA12, CA18, TM2, TM8): the QR code of an open appointment of the patient of the
 * session, holding only its id (the same one as spec 024). Ownership is checked before the
 * status, so an appointment of someone else reads as missing whatever its status.
 */
final readonly class GetPatientAppointmentQrUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private QrCodeGeneratorInterface $qrCodeGenerator,
    ) {}

    /**
     * The SVG of the QR code, or null when the appointment does not exist or is not the patient's.
     *
     * @throws AppointmentAplicationExceptions when the appointment of the patient is closed
     */
    public function execute(GetAppointmentByIdDTO $dto): ?string
    {
        $patientId = $this->authorization->currentPatientId();
        $appointmentId = new AppointmentId($dto->appointmentId);

        $appointment = $this->appointmentsService->findById($appointmentId);

        if ($appointment === null || $appointment->PatientId()->value !== $patientId) {
            return null;
        }

        if (! $appointment->Status()->isAssigned() && ! $appointment->Status()->isRescheduled()) {
            throw AppointmentAplicationExceptions::qrOnlyForOpenAppointments();
        }

        return $this->qrCodeGenerator->svg($appointmentId->value);
    }
}
