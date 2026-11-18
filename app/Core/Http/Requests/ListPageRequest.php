<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 028 (P6): the page and the size of a list with no search or filters of its own.
 */
final class ListPageRequest extends FormRequest
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
        return $this->pageRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->pageMessages();
    }
}
