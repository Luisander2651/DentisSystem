<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Http\Requests;

use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BR-7: both filters are optional and combinable, and an empty string means "no filter"
 * - the DTO already maps '' to null, so `nullable` keeps that contract instead of
 * rejecting `?role=`.
 */
final class GetUsersRequest extends FormRequest
{
    use CanonicalisesRoleInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            'role' => ['sometimes', 'nullable', 'string', Rule::in(UserRoleId::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El estado debe ser active o inactive.',
            'role.in' => 'El rol debe ser Administrador, Asistente o Doctor.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->canonicaliseRoleInput('role');
    }
}
