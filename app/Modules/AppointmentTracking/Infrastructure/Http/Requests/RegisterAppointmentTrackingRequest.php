<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Http\Requests;

use App\Core\Authorization\AuthorizationServiceInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 020 (CA8, A19, A30, D23): the same fields and rules as completing an appointment, with the
 * Spanish texts of the dialog under each field. Symptoms are optional; each one that comes must
 * be written.
 */
final class RegisterAppointmentTrackingRequest extends FormRequest
{
    private const DURATION = 'Escribe una duración de 1 a 365 días.';

    private const FREQUENCY = 'Escribe de 1 a 24 veces al día.';

    /**
     * TM3: whoever may not register a follow-up is refused whatever the body, before the rules.
     * The use case asks again, with the ownership of the appointment (P5).
     */
    public function authorize(AuthorizationServiceInterface $authorization): bool
    {
        if (! $authorization->can('appointment-tracking.create') && ! $authorization->can('appointment-tracking.create-own')) {
            $authorization->assertCan('appointment-tracking.create');
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'symptoms' => ['sometimes', 'nullable', 'array', 'max:20'],
            'symptoms.*' => ['required', 'string', 'max:255'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'procedure_performed' => ['required', 'string', 'max:2000'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'prescriptions' => ['sometimes', 'nullable', 'array', 'max:20'],
            'prescriptions.*' => ['array'],
            'prescriptions.*.medication' => ['required', 'string', 'max:255'],
            'prescriptions.*.dosage' => ['required', 'string', 'max:255'],
            'prescriptions.*.durationDays' => ['required', 'integer', 'between:1,365'],
            'prescriptions.*.dailyFrequency' => ['required', 'integer', 'between:1,24'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo de la consulta es obligatorio.',
            'reason.string' => 'El motivo de la consulta es obligatorio.',
            'reason.max' => 'El motivo de la consulta admite hasta 2000 caracteres.',
            'symptoms.array' => 'Los síntomas no tienen un formato válido.',
            'symptoms.max' => 'Escribe hasta 20 síntomas.',
            'symptoms.*.required' => 'Escribe el síntoma o quítalo.',
            'symptoms.*.string' => 'Escribe el síntoma o quítalo.',
            'symptoms.*.max' => 'Cada síntoma admite hasta 255 caracteres.',
            'diagnosis.required' => 'El diagnóstico es obligatorio.',
            'diagnosis.string' => 'El diagnóstico es obligatorio.',
            'diagnosis.max' => 'El diagnóstico admite hasta 2000 caracteres.',
            'procedure_performed.required' => 'El procedimiento realizado es obligatorio.',
            'procedure_performed.string' => 'El procedimiento realizado es obligatorio.',
            'procedure_performed.max' => 'El procedimiento realizado admite hasta 2000 caracteres.',
            'observations.string' => 'Las observaciones no tienen un formato válido.',
            'observations.max' => 'Las observaciones admiten hasta 2000 caracteres.',
            'recommendations.string' => 'Las recomendaciones no tienen un formato válido.',
            'recommendations.max' => 'Las recomendaciones admiten hasta 2000 caracteres.',
            'prescriptions.array' => 'Las recetas no tienen un formato válido.',
            'prescriptions.max' => 'Añade hasta 20 recetas.',
            'prescriptions.*.array' => 'La receta no tiene un formato válido.',
            'prescriptions.*.medication.required' => 'Escribe el medicamento.',
            'prescriptions.*.medication.string' => 'Escribe el medicamento.',
            'prescriptions.*.medication.max' => 'El medicamento admite hasta 255 caracteres.',
            'prescriptions.*.dosage.required' => 'Escribe la dosis.',
            'prescriptions.*.dosage.string' => 'Escribe la dosis.',
            'prescriptions.*.dosage.max' => 'La dosis admite hasta 255 caracteres.',
            'prescriptions.*.durationDays.required' => self::DURATION,
            'prescriptions.*.durationDays.integer' => self::DURATION,
            'prescriptions.*.durationDays.between' => self::DURATION,
            'prescriptions.*.dailyFrequency.required' => self::FREQUENCY,
            'prescriptions.*.dailyFrequency.integer' => self::FREQUENCY,
            'prescriptions.*.dailyFrequency.between' => self::FREQUENCY,
            'prescriptions.*.instructions.string' => 'Las indicaciones no tienen un formato válido.',
            'prescriptions.*.instructions.max' => 'Las indicaciones admiten hasta 2000 caracteres.',
        ];
    }
}
