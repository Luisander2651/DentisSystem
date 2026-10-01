<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Infrastructure\Listeners;

use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\whatsApp\Aplication\DTOs\SendConfirmationAppointmentMessageDTO;
use App\Modules\whatsApp\Aplication\UseCases\SendAppointmentConfirmationUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Spec 015 (CA18): logs identify the appointment, never the patient's phone or name.
 */
class CreatedAppointmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * El número de veces que el trabajo puede ser intentado si falla.
     * Ideal para llamadas a APIs externas como WhatsApp.
     */
    public int $tries = 3;

    /**
     * Segundos a esperar antes de reintentar el trabajo.
     */
    public int $backoff = 15;

    public function __construct(
        private SendAppointmentConfirmationUseCase $useCase
    ) {}

    public function handle(ScheduledAppointment $event): void
    {
        $appointmentId = $event->appointmentEntity->Id()->value;

        Log::info('CreatedAppointmentListener: Evento ScheduledAppointment recibido', [
            'appointmentId' => $appointmentId,
        ]);

        if (trim($event->customerPhone) === '') {
            Log::warning('CreatedAppointmentListener: Se omite el envío de WhatsApp porque no existe teléfono de contacto', [
                'appointmentId' => $appointmentId,
                'patientId' => $event->appointmentEntity->PatientId()->value,
            ]);

            return;
        }

        try {
            $dto = new SendConfirmationAppointmentMessageDTO(
                customerPhone: $event->customerPhone,
                customerName: $event->customerName,
                date: $event->date,
                time: $event->time
            );

            $this->useCase->execute($dto);

            Log::info('CreatedAppointmentListener: UseCase ejecutado exitosamente', [
                'appointmentId' => $appointmentId,
            ]);
        } catch (\Exception $e) {
            Log::error('CreatedAppointmentListener: Error al enviar mensaje', [
                'appointmentId' => $appointmentId,
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }
}
