<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Requests;

use App\Core\Authorization\AuthorizationServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Spec 017: a request does one thing (reschedule, change the status or the reminder). Rescheduling
 * needs the date and the time together and cannot travel with a status; the status only moves to
 * completada or cancelada (CA2, CA6, 007 CA4).
 */
final class UpdateAppointmentRequest extends FormRequest
{
    /**
     * CA10: whoever is not the administrator is denied whatever the body, so the permission is
     * checked before the rules. The use case asks again (P5).
     */
    public function authorize(AuthorizationServiceInterface $authorization): bool
    {
        $authorization->assertCan('appointments.update');

        return true;
    }

    /**
     * A request that carries the date or the time is a reschedule, so it needs both even when they
     * arrive empty, as the dialog sends them cleared (CA2, /review R1). An empty body asks for neither.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $reschedules = $this->exists('date') || $this->exists('time');

        return [
            'date' => ['bail', Rule::requiredIf($reschedules), 'date_format:Y-m-d'],
            'time' => ['bail', Rule::requiredIf($reschedules), 'date_format:H:i,H:i:s'],
            'status' => [Rule::in(['completada', 'cancelada']), 'prohibits:date,time'],
            'whatsapp_reminder' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Necesitas proporcionar fecha y hora para reprogramar la cita.',
            'time.required' => 'Necesitas proporcionar fecha y hora para reprogramar la cita.',
            'date.date_format' => 'La fecha no tiene un formato válido.',
            'time.date_format' => 'La hora no tiene un formato válido.',
            'status.in' => 'El estado solo puede cambiar a completada o cancelada.',
            'status.prohibits' => 'No puedes reprogramar y cambiar el estado de la cita a la vez.',
            'whatsapp_reminder.boolean' => 'El recordatorio de WhatsApp debe ser sí o no.',
        ];
    }
}
