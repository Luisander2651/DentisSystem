<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;

final readonly class UpdateAppointmentUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(UpdateAppointmentDTO $updateAppointmentDTO): void
    {
        if (!$updateAppointmentDTO->status && !$updateAppointmentDTO->whatsappReminder) {
            $this->validateReschedule($updateAppointmentDTO);
        }

        $appointmentId = new AppointmentId($updateAppointmentDTO->appointmentId);
        $appointment = $this->appointmentsService->findById($appointmentId);
        
        if (!$appointment) {
            throw AppointmentException::notFound($appointmentId);
        }

        $fields = $updateAppointmentDTO->fieldsToUpdate();

        $this->rescheduleAppointment($appointment, $fields);

        $this->manageStatusAndWhatsappReminderChange($appointment, $fields);

        $this->appointmentsService->saveAppointment($appointment);
    }

    private function validateReschedule(UpdateAppointmentDTO $updateAppointmentDTO): void
    {
        if (($updateAppointmentDTO->date && !$updateAppointmentDTO->time) || (!$updateAppointmentDTO->date && $updateAppointmentDTO->time)) {
            throw AppointmentAplicationExceptions::rescheduleRequiresDateAndTime();
        }
    }

    private function rescheduleAppointment(AppointmentEntity $appointment, array $fields): void
    {
        if (isset($fields['date']) || isset($fields['time'])) {
            $appointment->reschedule(
                isset($fields['date']) ? AppointmentDate::fromString($fields['date']) : null,
                isset($fields['time']) ? AppointmentTime::fromString($fields['time']) : null
            );
        }
    }

    private function updateStatus(AppointmentEntity $appointment, string $status): void
    {
        $newStatus = AppointmentStatus::fromString($status);
        
        if ($newStatus->isCompleted()) {
            $appointment->complete();
        } elseif ($newStatus->isCancelled()) {
            $appointment->cancel();
        } else {
            throw AppointmentException::invalidStatusTransition();
        }
    }

    private function updateWhatsappReminder(AppointmentEntity $appointment): void
    {
        $appointment->updateWhatsappReminder();
    }

    private function manageStatusAndWhatsappReminderChange(AppointmentEntity $appointment, array $fields): void
    {
        foreach ($fields as $field => $value) {
            switch ($field) {
                case 'status':
                    $this->updateStatus($appointment, $value);
                    break;
                case 'whatsapp_reminder':
                    $this->updateWhatsappReminder($appointment);
                    break;
            }
        }
    }
}