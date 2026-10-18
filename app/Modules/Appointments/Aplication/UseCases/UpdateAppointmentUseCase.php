<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\Exceptions\RescheduleAndStatusTogetherException;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\Service\ScheduleAvailabilityChecker;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;

/**
 * Spec 017: a request reschedules (date and time together, with the reminder if it comes), or
 * changes the status (to completada or cancelada, with the reminder if it comes), or only the
 * reminder. Rescheduling and changing the status at once is refused (CA6). Checks, in order:
 * input, existence, closed appointment, past, schedule conflict (plan D8).
 */
final readonly class UpdateAppointmentUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private ScheduleAvailabilityChecker $scheduleAvailabilityChecker,
    ) {}

    public function execute(UpdateAppointmentDTO $updateAppointmentDTO): void
    {
        $this->authorization->assertCan('appointments.update');

        $reschedules = $updateAppointmentDTO->date !== null || $updateAppointmentDTO->time !== null;

        if ($reschedules) {
            $this->validateReschedule($updateAppointmentDTO);
        }

        $appointmentId = new AppointmentId($updateAppointmentDTO->appointmentId);
        $appointment = $this->appointmentsService->findById($appointmentId);

        if (! $appointment) {
            throw AppointmentException::notFound($appointmentId);
        }

        $fields = $updateAppointmentDTO->fieldsToUpdate();

        if ($reschedules) {
            $this->rescheduleAppointment($appointment, $fields['date'], $fields['time']);
        }

        if (isset($fields['status'])) {
            $this->updateStatus($appointment, $fields['status']);
        }

        if (isset($fields['whatsapp_reminder'])) {
            $appointment->updateWhatsappReminder();
        }

        $this->appointmentsService->saveAppointment($appointment);
    }

    private function validateReschedule(UpdateAppointmentDTO $updateAppointmentDTO): void
    {
        if ($updateAppointmentDTO->status !== null) {
            throw RescheduleAndStatusTogetherException::create();
        }

        if ($updateAppointmentDTO->date === null || $updateAppointmentDTO->time === null) {
            throw AppointmentAplicationExceptions::rescheduleRequiresDateAndTime();
        }
    }

    private function rescheduleAppointment(AppointmentEntity $appointment, string $date, string $time): void
    {
        $newDate = AppointmentDate::fromString($date);
        $newTime = AppointmentTime::fromString($time);

        $appointment->reschedule($newDate, $newTime, now()->toImmutable());

        $this->scheduleAvailabilityChecker->ensureAvailable(
            $newDate,
            $newTime,
            $appointment->TreatmentId(),
            excludeAppointmentId: $appointment->Id(),
        );
    }

    private function updateStatus(AppointmentEntity $appointment, string $status): void
    {
        $newStatus = AppointmentStatus::fromString($status);

        if ($newStatus->isCompleted()) {
            // Spec 018 (D1): completing without the clinical follow-up stays with the
            // administrator; the assistant may edit the appointment but not close it this way.
            $this->authorization->assertCan('appointments.mark-completed');
            $appointment->complete();
        } elseif ($newStatus->isCancelled()) {
            $appointment->cancel();
        } else {
            throw AppointmentException::invalidStatusTransition();
        }
    }
}
