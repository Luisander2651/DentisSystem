<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Requests;

use App\Core\Http\Requests\TrimsPasswordFields;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BR-12 (SECURITY-05): before this class the controller read every field with
 * $request->string(...)->value(), which degrades a missing field to ''. That is how a
 * body-less POST reached UserName::create('', '') and killed the worker (BR-10).
 */
final class RegisterUserRequest extends FormRequest
{
    use CanonicalisesRoleInput;

    // The trait's prepareForValidation() would be shadowed by the one this class
    // declares, silently switching the trimming off, so it is aliased and called
    // explicitly instead.
    use TrimsPasswordFields {
        prepareForValidation as private trimPasswordFields;
    }

    /**
     * BR-14: `password` is exempt from Laravel's TrimStrings middleware while
     * `new_password` is not, so without this the same value produced two different
     * credentials depending on the endpoint.
     *
     * @return list<string>
     */
    protected function passwordFields(): array
    {
        return ['password'];
    }

    public function authorize(): bool
    {
        // Authorisation is the job of the only.admin middleware and of assertCan() inside
        // the use case (BR-1). Repeating it here would add a third, divergent copy.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            // BR-11 (SECURITY-12): the same 8-character floor Unit 3 applied to the
            // public registration and the password reset.
            'password' => ['required', 'string', 'min:8'],
            // BR-16: case-insensitive against the canonical roles vocabulary.
            'role_id' => ['required', 'string', Rule::in(UserRoleId::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.max' => 'El nombre no puede superar los 50 caracteres.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.max' => 'El apellido no puede superar los 50 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.in' => 'El rol debe ser Administrador, Asistente o Doctor.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimPasswordFields();
        $this->canonicaliseRoleInput();
    }
}
