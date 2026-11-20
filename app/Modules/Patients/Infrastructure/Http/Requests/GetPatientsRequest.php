<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Http\Requests;

use App\Core\Http\Requests\PageQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Spec 028 (P6, A35): the page of the patients list, its size, the status filter and the search.
 * An empty status or search means none.
 */
final class GetPatientsRequest extends FormRequest
{
    use PageQueryRules;

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
            ...$this->pageRules(),
            ...$this->searchRules(),
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->pageMessages(),
            ...$this->searchMessages(),
            'status.string' => 'El estado debe ser active o inactive.',
            'status.in' => 'El estado debe ser active o inactive.',
        ];
    }

    public function status(): ?string
    {
        $status = $this->validated('status');

        return is_string($status) && $status !== '' ? $status : null;
    }
}
