<?php

declare(strict_types=1);

use App\Core\Http\Requests\ListPageRequest;
use App\Core\Http\Requests\PageQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 028 (CA9, CA13; TM4; P6): the page, the size and the search of a list are checked before
 * anything is read; a bad value is a 422 with its message in Spanish, never a 500.
 */

final class SearchablePageTestRequest extends FormRequest
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
        return [...$this->pageRules(), ...$this->searchRules()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...$this->pageMessages(), ...$this->searchMessages()];
    }
}

beforeEach(function () {
    Route::get('/api/test/list-page', function (ListPageRequest $request) {
        $page = $request->pageRequest(10);

        return response()->json(['page' => $page->page, 'size' => $page->size]);
    });

    Route::get('/api/test/searchable-page', function (SearchablePageTestRequest $request) {
        return response()->json(['search' => $request->searchText(), 'size' => $request->pageRequest(12)->size]);
    });
});

it('reads the page and the size, with the size of the list when it is missing', function () {
    $this->getJson('/api/test/list-page')->assertOk()->assertExactJson(['page' => 1, 'size' => 10]);
    $this->getJson('/api/test/list-page?page=3&per_page=20')->assertOk()->assertExactJson(['page' => 3, 'size' => 20]);
});

it('lowers a size above 50 to 50 without an error', function () {
    $this->getJson('/api/test/list-page?per_page=10000')->assertOk()->assertJsonPath('size', 50);
});

it('refuses a page or a size that is not a whole number from 1', function (string $query, string $field, string $message) {
    $this->getJson('/api/test/list-page?'.$query)
        ->assertUnprocessable()
        ->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'a page that is text' => ['page=abc', 'page', 'La página debe ser un número entero mayor que cero.'],
    'page 0' => ['page=0', 'page', 'La página debe ser un número entero mayor que cero.'],
    'a page as a list' => ['page[]=1', 'page', 'La página debe ser un número entero mayor que cero.'],
    'size 0' => ['per_page=0', 'per_page', 'El tamaño de página debe ser un número entero mayor que cero.'],
    'a size with decimals' => ['per_page=1.5', 'per_page', 'El tamaño de página debe ser un número entero mayor que cero.'],
]);

it('trims the search and takes an empty one as no search', function () {
    $this->getJson('/api/test/searchable-page?search=%20Valeria%20')->assertOk()->assertJsonPath('search', 'Valeria');
    $this->getJson('/api/test/searchable-page?search=')->assertOk()->assertJsonPath('search', null);
    $this->getJson('/api/test/searchable-page')->assertOk()->assertJsonPath('search', null);
});

it('refuses a search longer than 100 characters or that is not text', function (string $query) {
    $this->getJson('/api/test/searchable-page?'.$query)
        ->assertUnprocessable()
        ->assertJsonPath('errors.search.0', 'La búsqueda admite como máximo 100 caracteres de texto.');
})->with([
    '101 characters' => ['search='.str_repeat('a', 101)],
    'a list' => ['search[]=x'],
]);

it('accepts a search of exactly 100 characters', function () {
    $this->getJson('/api/test/searchable-page?search='.str_repeat('a', 100))->assertOk();
});
