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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required_with:time', 'date_format:Y-m-d'],
            'time' => ['required_with:date', 'date_format:H:i,H:i:s'],
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
            'date.required_with' => 'Necesitas proporcionar fecha y hora para reprogramar la cita.',
            'time.required_with' => 'Necesitas proporcionar fecha y hora para reprogramar la cita.',
            'date.date_format' => 'La fecha no tiene un formato válido.',
            'time.date_format' => 'La hora no tiene un formato válido.',
            'status.in' => 'El estado solo puede cambiar a completada o cancelada.',
            'status.prohibits' => 'No puedes reprogramar y cambiar el estado de la cita a la vez.',
            'whatsapp_reminder.boolean' => 'El recordatorio de WhatsApp debe ser sí o no.',
        ];
    }
}
