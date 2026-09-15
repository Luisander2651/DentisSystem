<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Requests;

use App\Core\Http\Requests\TrimsPasswordFields;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    use CanonicalisesRoleInput;

    // Aliased so the class's own prepareForValidation() does not shadow it and silently
    // switch the password trimming off.
    use TrimsPasswordFields {
        prepareForValidation as private trimPasswordFields;
    }

    /**
     * BR-14: `new_password` is NOT in TrimStrings' exempt list while `password` is, which
     * is precisely why the two endpoints stored different credentials for the same value
     * before this.
     *
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
     * Every field is optional - the endpoint performs a partial update (BR-5). What is
     * NOT optional is that at least one of them is present; see withValidator().
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // `nullable` pairs with prepareForValidation(), which turns an empty string
            // into null: '' means "not provided" on this endpoint, so the remaining rules
            // are skipped rather than failing a field nobody meant to change.
            'first_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'role_id' => ['sometimes', 'nullable', 'string', Rule::in(UserRoleId::all())],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            'new_password' => ['sometimes', 'nullable', 'string', 'min:8'],
            // BR-23: the email is immutable after creation (BR-4). It used to be dropped
            // in silence, so a client believed it had changed an address when nothing had
            // happened. `prohibited` turns that into an explicit 422.
            'email' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.max' => 'El nombre no puede superar los 50 caracteres.',
            'last_name.max' => 'El apellido no puede superar los 50 caracteres.',
            'role_id.in' => 'El rol debe ser Administrador, Asistente o Doctor.',
            'status.in' => 'El estado debe ser active o inactive.',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'email.prohibited' => 'El correo electrónico no se puede modificar una vez creado el usuario.',
        ];
    }

    /**
     * BR-22: an update carrying no usable field is a malformed request, not a conflict of
     * state, so it belongs here as a 422 rather than reaching the use case and surfacing
     * as the 409 it used to produce.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $updatable = ['first_name', 'last_name', 'role_id', 'status', 'new_password'];

            foreach ($updatable as $field) {
                $value = $this->input($field);

                if (is_string($value) && $value !== '') {
                    return;
                }
            }

            $validator->errors()->add(
                'first_name',
                'Debe proporcionar al menos un campo para actualizar.',
            );
        });
    }

    /**
     * An empty string means "not provided" throughout this endpoint: UpdateUserDto::create()
     * already maps '' to null, and the real client sends every field on every submit,
     * leaving the untouched ones empty. Without this, `new_password: ''` would hit min:8
     * and a rename would be rejected for a password the admin never meant to change.
     *
     * `email` is deliberately NOT stripped: it is `prohibited`, and removing it here would
     * turn an explicit attempt to change the address back into the silent discard BR-23
     * exists to stop.
     */
    protected function prepareForValidation(): void
    {
        $this->trimPasswordFields();
        $this->canonicaliseRoleInput();

        $blanked = [];

        foreach (['first_name', 'last_name', 'role_id', 'status', 'new_password'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) === '') {
                $blanked[$field] = null;
            }
        }

        if ($blanked !== []) {
            $this->merge($blanked);
        }
    }
}
