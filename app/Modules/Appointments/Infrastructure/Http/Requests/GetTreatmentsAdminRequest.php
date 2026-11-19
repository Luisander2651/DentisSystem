<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Requests;

use App\Core\Http\Requests\PageQueryRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 028 (P6): the page of the treatments list, its size and the search.
 */
final class GetTreatmentsAdminRequest extends FormRequest
{
    use PageQueryRules;

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
            ...$this->pageRules(),
            ...$this->searchRules(),
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
        ];
    }
}
