<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 028 (P6, A10): the page of a section of the start ("Citas sin cerrar", "Completadas sin
 * seguimiento"). Only the page is read: the size is fixed by the domain, so a `per_page` sent by
 * the client is ignored.
 */
final class PendingPageRequest extends FormRequest
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
        return ['page' => $this->pageRules()['page']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_intersect_key($this->pageMessages(), array_flip(['page.integer', 'page.min']));
    }

    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }
}
