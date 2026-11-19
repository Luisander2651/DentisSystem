<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases\ListGalleryImagesPageUseCase;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\ListPromotionsPageUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use Illuminate\Support\Carbon;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;
use Tests\Support\ActingAsPatient;

uses(ContentManagementIntegrationTestCase::class, ActingAsPatient::class);

/**
 * Spec 028 (CA1, CA5, CA6, CA13, CA14; P5, P6, P7): each content list of the panel answers one page
 * of twelve, always as {"data": [...], "meta"}, with the counts by status of the whole list. The
 * lists of the public site do not change.
 */
function contentPaginationGallery(int $count, int $hidden = 0): void
{
    foreach (range(1, $count) as $number) {
        GalleryImageModel::create([
            'url' => "/storage/galeria/imagen-{$number}.jpg",
            'description' => "Imagen {$number}",
            'status' => $number <= $hidden ? 'hidden' : 'visible',
        ])->forceFill(['created_at' => Carbon::parse('2026-01-01 08:00:00')->addMinutes($number)])->save();
    }
}

function contentPaginationDenying(): AuthorizationServiceInterface
{
    return new class implements AuthorizationServiceInterface
    {
        public function assertCan(string $permission): void
        {
            throw AuthorizationException::forbidden($permission);
        }

        public function can(string $permission): bool
        {
            return false;
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            throw AuthorizationException::unauthenticated();
        }
    };
}

// --- Galería ---

it('answers the first page of the gallery with its meta and the counts by status', function () {
    $this->actingAsAdmin();
    contentPaginationGallery(30, 4);

    $this->getJson('/api/v1/gallery-images')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('data.0.description', 'Imagen 30')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
            'from' => 1,
            'to' => 12,
            'counts' => ['total' => 30, 'visible' => 26, 'hidden' => 4],
        ]);
});

it('always answers a list, also with a single image', function () {
    $this->actingAsAdmin();
    contentPaginationGallery(1);

    $this->getJson('/api/v1/gallery-images')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.description', 'Imagen 1')
        ->assertJsonPath('meta.total', 1);
});

it('answers the page asked for, the last one beyond it and never more than fifty', function (string $query, int $count, int $page) {
    $this->actingAsAdmin();
    contentPaginationGallery(60);

    $this->getJson('/api/v1/gallery-images'.$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'second page' => ['?page=2', 12, 2],
    'beyond the last' => ['?page=99', 12, 5],
    'ten thousand per page' => ['?per_page=10000', 50, 1],
]);

it('refuses the gallery of the panel with 403 and without meta whoever is not an administrator', function (string $actor) {
    contentPaginationGallery(3);

    match ($actor) {
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'doctor' => $this->actingAsNonAdminUser('Doctor'),
        'paciente' => $this->actingAsPatient(),
        'inactivo' => $this->actingAsInactiveAdmin(),
    };

    $this->getJson('/api/v1/gallery-images')
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
})->with(['asistente', 'doctor', 'paciente', 'inactivo']);

it('neither reads nor counts the gallery when the permission is denied', function () {
    $repository = $this->mock(GalleryImageRepositoryInterface::class);
    $repository->shouldNotReceive('findPage');
    $repository->shouldNotReceive('countByStatus');
    $this->app->instance(AuthorizationServiceInterface::class, contentPaginationDenying());

    expect(fn () => app(ListGalleryImagesPageUseCase::class)->execute(PageRequest::create(1, 12)))
        ->toThrow(AuthorizationException::class);
});

it('rejects invalid page parameters of the gallery with 422', function (string $query, string $field) {
    $this->actingAsAdmin();

    $this->getJson('/api/v1/gallery-images'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
]);

it('keeps the public gallery as it was', function () {
    contentPaginationGallery(15, 2);

    $this->getJson('/api/v1/public/gallery-images?status=visible')
        ->assertOk()
        ->assertJsonCount(13)
        ->assertJsonMissingPath('meta');
});

// --- Promociones ---

function contentPaginationPromotions(int $count, int $hidden = 0): void
{
    foreach (range(1, $count) as $number) {
        PromotionModel::create([
            'name' => "Promoción {$number}",
            'description' => "Descripción {$number}",
            'discount_percentage' => 10,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => $number <= $hidden ? 'oculto' : 'visible',
        ])->forceFill(['created_at' => Carbon::parse('2026-01-01 08:00:00')->addMinutes($number)])->save();
    }
}

it('answers the first page of the promotions with its meta and the counts by status', function () {
    $this->actingAsAdmin();
    contentPaginationPromotions(30, 4);

    $this->getJson('/api/v1/promotions')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('data.0.name', 'Promoción 30')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
            'from' => 1,
            'to' => 12,
            'counts' => ['total' => 30, 'visible' => 26, 'hidden' => 4],
        ]);
});

it('always answers a list of promotions, also with a single one', function () {
    $this->actingAsAdmin();
    contentPaginationPromotions(1);

    $this->getJson('/api/v1/promotions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Promoción 1')
        ->assertJsonPath('meta.total', 1);
});

it('answers the page of promotions asked for, the last one beyond it and never more than fifty', function (string $query, int $count, int $page) {
    $this->actingAsAdmin();
    contentPaginationPromotions(60);

    $this->getJson('/api/v1/promotions'.$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'second page' => ['?page=2', 12, 2],
    'beyond the last' => ['?page=99', 12, 5],
    'ten thousand per page' => ['?per_page=10000', 50, 1],
]);

it('refuses the promotions of the panel with 403 and without meta whoever is not an administrator', function (string $actor) {
    contentPaginationPromotions(3);

    match ($actor) {
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'doctor' => $this->actingAsNonAdminUser('Doctor'),
        'paciente' => $this->actingAsPatient(),
        'inactivo' => $this->actingAsInactiveAdmin(),
    };

    $this->getJson('/api/v1/promotions')
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
})->with(['asistente', 'doctor', 'paciente', 'inactivo']);

it('neither reads nor counts the promotions when the permission is denied', function () {
    $repository = $this->mock(PromotionRepositoryInterface::class);
    $repository->shouldNotReceive('findPage');
    $repository->shouldNotReceive('countByStatus');
    $this->app->instance(AuthorizationServiceInterface::class, contentPaginationDenying());

    expect(fn () => app(ListPromotionsPageUseCase::class)->execute(PageRequest::create(1, 12)))
        ->toThrow(AuthorizationException::class);
});

it('rejects invalid page parameters of the promotions with 422', function (string $query, string $field) {
    $this->actingAsAdmin();

    $this->getJson('/api/v1/promotions'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
]);

it('keeps the public promotions without meta', function () {
    contentPaginationPromotions(15, 2);

    $this->getJson('/api/v1/public/promotions')
        ->assertOk()
        ->assertJsonMissingPath('meta');
});
