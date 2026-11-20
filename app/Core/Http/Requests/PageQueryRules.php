<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

use App\Core\Domain\Pagination\PageRequest;

/**
 * Spec 028 (CA9, CA13; TM4; P6): the rules and messages of `page`, `per_page` and `search`, shared
 * by every paged list. A size above PageRequest::MAX_SIZE is lowered there, never refused here.
 */
trait PageQueryRules
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function pageRules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pageMessages(): array
    {
        $page = 'La página debe ser un número entero mayor que cero.';
        $size = 'El tamaño de página debe ser un número entero mayor que cero.';

        return [
            'page.integer' => $page,
            'page.min' => $page,
            'per_page.integer' => $size,
            'per_page.min' => $size,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function searchRules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function searchMessages(): array
    {
        $search = 'La búsqueda admite como máximo 100 caracteres de texto.';

        return [
            'search.string' => $search,
            'search.max' => $search,
        ];
    }

    public function pageRequest(int $defaultSize): PageRequest
    {
        return PageRequest::create(
            (int) $this->validated('page', 1),
            (int) $this->validated('per_page', $defaultSize),
        );
    }

    public function searchText(): ?string
    {
        $search = trim((string) $this->validated('search', ''));

        return $search === '' ? null : $search;
    }
}
