<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    use TrimsPasswordFields;

    /**
     * @return list<string>
     */
    protected function passwordFields(): array
    {
        return ['new_password'];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * BR-18: the reset token must travel in the request body. Laravel's `all()`
     * merges the query string into the validated data, which would keep accepting
     * `?token=...`, so validation is restricted to the body explicitly.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : $this->request->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'El token de restablecimiento es obligatorio.',
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
