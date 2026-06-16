<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Infrastructure\Listeners;

use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\whatsApp\Aplication\DTOs\SendConfirmationAppointmentMessageDTO;
use App\Modules\whatsApp\Aplication\UseCases\SendAppointmentConfirmationUseCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
    )
    {}

    public function handle(ScheduledAppointment $event): void
    {
        Log::info('CreatedAppointmentListener: Evento ScheduledAppointment recibido', [
            'customerPhone' => $event->customerPhone,
            'customerName' => $event->customerName,
            'appointmentId' => $event->appointmentEntity->Id()->value,
        ]);

        try {
            $dto = new SendConfirmationAppointmentMessageDTO(
                customerPhone: $event->customerPhone,
                customerName: $event->customerName,
                date: $event->date,
                time: $event->time
            );

            Log::info('CreatedAppointmentListener: DTO creado, ejecutando UseCase', [
                'customerPhone' => $dto->customerPhone,
            ]);

            $this->useCase->execute($dto);

            Log::info('CreatedAppointmentListener: UseCase ejecutado exitosamente');
        } catch (\Exception $e) {
            Log::error('CreatedAppointmentListener: Error al enviar mensaje', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}