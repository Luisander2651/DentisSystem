<?php

declare(strict_types=1);

namespace App\Modules\Email\Infrastructure\Listeners;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Email\Aplication\UseCases\SendResetPasswordEmailUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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
            // Spec 015 (CA18): no e-mail address, name or trace in the logs.
            Log::info('SendPasswordResetListener: Evento SendEmailForChangePasswordEvent procesado');
        } catch (\Exception $e) {
            Log::error('SendPasswordResetListener: Error al procesar el evento', [
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }
}
