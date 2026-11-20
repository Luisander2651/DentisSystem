<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\Persistence\Eloquent\Models\GalleryImageModel;
use Illuminate\Support\Carbon;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/**
 * Spec 028 (CA5, CA7): the gallery of the panel is read one page at a time, newest first, and its
 * images are counted by status over the whole list.
 */
function galleryPageCreate(int $number, string $status = 'visible', ?Carbon $createdAt = null): GalleryImageModel
{
    $image = GalleryImageModel::create([
        'url' => "/storage/galeria/imagen-{$number}.jpg",
        'description' => "Imagen {$number}",
        'status' => $status,
    ]);
    $image->forceFill(['created_at' => $createdAt ?? Carbon::parse('2026-01-01 08:00:00')->addMinutes($number)])->save();

    return $image;
}

/**
 * @param  list<GalleryImageEntity>  $images
 * @return list<int>
 */
function galleryPageIds(array $images): array
{
    return array_map(fn (GalleryImageEntity $image): int => (int) $image->Id()->value, $images);
}

it('reads the third page of thirty images with the six left, newest first', function () {
    foreach (range(1, 30) as $number) {
        galleryPageCreate($number);
    }

    $first = app(GalleryService::class)->findPage(PageRequest::create(1, 12));
    $third = app(GalleryService::class)->findPage(PageRequest::create(3, 12));

    expect($first->items[0]->Description()->value)->toBe('Imagen 30')
        ->and($third->items)->toHaveCount(6)
        ->and($third->total)->toBe(30)
        ->and($third->items[5]->Description()->value)->toBe('Imagen 1');
});

it('gives the last page when the page asked for goes beyond it', function () {
    foreach (range(1, 13) as $number) {
        galleryPageCreate($number, $number % 2 === 0 ? 'hidden' : 'visible');
    }

    $page = app(GalleryService::class)->findPage(PageRequest::create(40, 12));

    expect($page->page)->toBe(2)->and($page->items)->toHaveCount(1);
});

it('gives every image exactly once across the pages when they share the creation time', function () {
    $sameTime = Carbon::parse('2026-01-01 08:00:00');

    foreach (range(1, 25) as $number) {
        galleryPageCreate($number, 'visible', $sameTime);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...galleryPageIds(app(GalleryService::class)->findPage(PageRequest::create($number, 12))->items)];
    }

    expect($seen)->toBe(GalleryImageModel::query()->pluck('id')->map(fn ($id): int => (int) $id)->sortDesc()->values()->all());
});

it('counts the whole gallery by status', function () {
    foreach (range(1, 5) as $number) {
        galleryPageCreate($number, $number <= 3 ? 'visible' : 'hidden');
    }

    expect(app(GalleryService::class)->countByStatus())->toBe(['total' => 5, 'visible' => 3, 'hidden' => 2]);
});

it('counts zero for an empty gallery', function () {
    expect(app(GalleryService::class)->countByStatus())->toBe(['total' => 0, 'visible' => 0, 'hidden' => 0]);
});
