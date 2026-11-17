<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 027: the form of /opinion. The name as the visitor wants it shown (2–80), the opinion
 * (20–255, the size of its column) and the consent to publish both. `website` is the trap field a
 * person never sees (CA6). Line breaks are counted as one character, as the visitor sees them (A10).
 * A wrong form comes back to /opinion with its errors (exception of P6 for web forms, A4).
 */
final class SubmitPublicTestimonialRequest extends FormRequest
{
    protected $redirectRoute = 'opinion';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('opinion'))) {
            $this->merge(['opinion' => str_replace("\r\n", "\n", $this->input('opinion'))]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'opinion' => ['required', 'string', 'min:20', 'max:255'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre como quieres que aparezca.',
            'name.string' => 'Escribe tu nombre como quieres que aparezca.',
            'name.min' => 'Tu nombre debe tener al menos 2 caracteres.',
            'name.max' => 'Tu nombre puede tener como máximo 80 caracteres.',
            'opinion.required' => 'Escribe tu opinión.',
            'opinion.string' => 'Escribe tu opinión.',
            'opinion.min' => 'Tu opinión debe tener al menos 20 caracteres.',
            'opinion.max' => 'Tu opinión puede tener como máximo 255 caracteres.',
            'consent.accepted' => 'Para enviarla, acepta que publiquemos tu nombre y tu opinión.',
        ];
    }

    /** True when the trap field a person never sees came filled: a robot (CA6). */
    public function isFromARobot(): bool
    {
        return filled($this->input('website'));
    }
}
