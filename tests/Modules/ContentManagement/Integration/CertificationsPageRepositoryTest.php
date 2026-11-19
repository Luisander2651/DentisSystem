<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationStatus;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models\CertificationModel;
use Illuminate\Support\Carbon;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/**
 * Spec 028 (CA5, CA7): the Certificaciones of the panel are read one page at a time, newest first, and
 * counted by status over the whole list.
 */
function certificationsPageCreate(int $number, ?string $status = null, ?Carbon $createdAt = null): CertificationModel
{
    $item = CertificationModel::create([
        'name' => "Certificación {$number}",
        'description' => "Descripción {$number}",
        'date' => '2025-06-01',
        'image_url' => "/storage/certificaciones/certificacion-{$number}.jpg",
        'status' => $status ?? CertificationStatus::visible()->value,
    ]);
    $item->forceFill(['created_at' => $createdAt ?? Carbon::parse('2026-01-01 08:00:00')->addMinutes($number)])->save();

    return $item;
}

/**
 * @param  list<CertificationEntity>  $items
 * @return list<int>
 */
function certificationsPageIds(array $items): array
{
    return array_map(fn (CertificationEntity $item): int => (int) $item->Id()->value, $items);
}

it('reads the third page of thirty with the six left, newest first', function () {
    $created = array_map(fn (int $number): int => (int) certificationsPageCreate($number)->id, range(1, 30));

    $first = app(CertificationsService::class)->findPage(PageRequest::create(1, 12));
    $third = app(CertificationsService::class)->findPage(PageRequest::create(3, 12));

    expect(certificationsPageIds($first->items)[0])->toBe($created[29])
        ->and($third->items)->toHaveCount(6)
        ->and($third->total)->toBe(30)
        ->and(certificationsPageIds($third->items)[5])->toBe($created[0]);
});

it('gives the last page when the page asked for goes beyond it, hidden ones included', function () {
    foreach (range(1, 13) as $number) {
        certificationsPageCreate($number, $number % 2 === 0 ? CertificationStatus::hidden()->value : null);
    }

    $page = app(CertificationsService::class)->findPage(PageRequest::create(40, 12));

    expect($page->page)->toBe(2)->and($page->items)->toHaveCount(1)->and($page->total)->toBe(13);
});

it('gives every item exactly once across the pages when they share the creation time', function () {
    $sameTime = Carbon::parse('2026-01-01 08:00:00');

    foreach (range(1, 25) as $number) {
        certificationsPageCreate($number, null, $sameTime);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...certificationsPageIds(app(CertificationsService::class)->findPage(PageRequest::create($number, 12))->items)];
    }

    expect($seen)->toBe(CertificationModel::query()->pluck('id')->map(fn ($id): int => (int) $id)->sortDesc()->values()->all());
});

it('counts the whole list by status', function () {
    foreach (range(1, 5) as $number) {
        certificationsPageCreate($number, $number <= 3 ? null : CertificationStatus::hidden()->value);
    }

    expect(app(CertificationsService::class)->countByStatus())->toBe(['total' => 5, 'visible' => 3, 'hidden' => 2]);
});

it('counts zero for an empty list', function () {
    expect(app(CertificationsService::class)->countByStatus())->toBe(['total' => 0, 'visible' => 0, 'hidden' => 0]);
});
