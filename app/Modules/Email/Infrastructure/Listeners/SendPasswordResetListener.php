<?php

declare(strict_types=1);

namespace App\Modules\Email\Infrastructure\Listeners;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Email\Aplication\UseCases\SendResetPasswordEmailUseCase;
use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\whatsApp\Aplication\DTOs\SendConfirmationAppointmentMessageDTO;
use App\Modules\whatsApp\Aplication\UseCases\SendAppointmentConfirmationUseCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class SendPasswordResetListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * El número de veces que el trabajo puede ser intentado si falla.
     * Ideal para llamadas a APIs externas como el servicio de email.
     */
    public int $tries = 3;

    /**
     * Segundos a esperar antes de reintentar el trabajo.
     */
    public int $backoff = 15;

    public function __construct(
        private SendResetPasswordEmailUseCase $sendResetPasswordEmailUseCase,
    ) {}

    public function handle(SendEmailForChangePasswordEvent $event): void
    {
        try {
            $this->sendResetPasswordEmailUseCase->execute(
                email: $event->customerEmail,
                name: $event->customerName,
                token: $event->token
            );
            Log::info('SendPasswordResetListener: Evento SendEmailForChangePasswordEvent recibido', [
                'customerEmail' => $event->customerEmail,
                'customerName' => $event->customerName,
            ]);
        } catch (\Exception $e) {
            Log::error('SendPasswordResetListener: Error al procesar el evento', [
                'error' => $e->getMessage(),
                'customerEmail' => $event->customerEmail,
                'trace' => $e->getTraceAsString(),
            ]);
             throw $e;
        }
    }
}