<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Service\TreatmentsService;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 028 (CA4, CA5, CA7; TM3, D16): the treatments list is read one page at a time, by name,
 * with a literal search over the name, the description or the minutes, without telling accents
 * apart; its stats count the whole catalogue.
 */
function treatmentsPageService(): TreatmentsService
{
    return app(TreatmentsService::class);
}

/**
 * @param  list<TreatmentEntity>  $treatments
 * @return list<string>
 */
function treatmentsPageNames(array $treatments): array
{
    return array_map(fn (TreatmentEntity $treatment): string => $treatment->name()->value, $treatments);
}

it('reads the third page of thirty treatments by name', function () {
    for ($i = 1; $i <= 30; $i++) {
        $this->createTreatment(['name' => sprintf('Tratamiento %02d', $i), 'description' => 'Descripción', 'time' => 30]);
    }

    $page = treatmentsPageService()->findPage(PageRequest::create(3, 12), null);

    expect($page->items)->toHaveCount(6)
        ->and($page->total)->toBe(30)
        ->and(treatmentsPageNames($page->items)[0])->toBe('Tratamiento 25')
        ->and(treatmentsPageNames($page->items)[5])->toBe('Tratamiento 30');
});

it('gives the last page when the page asked for goes beyond it', function () {
    for ($i = 1; $i <= 13; $i++) {
        $this->createTreatment(['name' => "Tratamiento {$i}"]);
    }

    $page = treatmentsPageService()->findPage(PageRequest::create(50, 12), null);

    expect($page->page)->toBe(2)->and($page->items)->toHaveCount(1);
});

it('gives every treatment exactly once across the pages when they share the name', function () {
    for ($i = 1; $i <= 25; $i++) {
        $this->createTreatment(['name' => 'Limpieza']);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...array_map(
            fn (TreatmentEntity $treatment): int => (int) $treatment->id()->value,
            treatmentsPageService()->findPage(PageRequest::create($number, 12), null)->items,
        )];
    }

    expect($seen)->toBe(TreatmentModel::query()->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all());
});

it('finds a treatment by its name, its description or its minutes', function (string $search, array $expected) {
    $this->createTreatment(['name' => 'Limpieza', 'description' => 'Retira el sarro', 'time' => 30]);
    $this->createTreatment(['name' => 'Endodoncia', 'description' => 'Trata el conducto', 'time' => 90]);
    $this->createTreatment(['name' => 'Blanqueamiento', 'description' => 'Aclara el esmalte', 'time' => 60]);

    $page = treatmentsPageService()->findPage(PageRequest::create(1, 12), $search);

    expect(treatmentsPageNames($page->items))->toBe($expected);
})->with([
    'name' => ['endo', ['Endodoncia']],
    'description' => ['SARRO', ['Limpieza']],
    'minutes' => ['60', ['Blanqueamiento']],
    'part of the minutes' => ['0', ['Blanqueamiento', 'Endodoncia', 'Limpieza']],
]);

it('searches the wildcards as literal text', function (string $search) {
    $this->createTreatment(['name' => 'Limpieza', 'description' => 'Retira el sarro']);
    $this->createTreatment(['name' => 'Endodoncia', 'description' => 'Trata el conducto']);

    expect(treatmentsPageService()->findPage(PageRequest::create(1, 12), $search)->total)->toBe(0);
})->with(['%', '_', "' OR 1=1"]);

it('searches without telling accents apart, the ñ as a letter of its own', function (string $search, array $expected) {
    $this->createTreatment(['name' => 'Extracción', 'description' => 'Pieza dañada']);
    $this->createTreatment(['name' => 'Ortodoncia', 'description' => 'Corrección de la mordida']);

    $page = treatmentsPageService()->findPage(PageRequest::create(1, 12), $search);

    expect(treatmentsPageNames($page->items))->toBe($expected);
})->with([
    'without the accent' => ['extraccion', ['Extracción']],
    'upper case with the accent' => ['CORRECCIÓN', ['Ortodoncia']],
    'ñ written as n' => ['danada', []],
    'ñ' => ['dañada', ['Extracción']],
]);

it('gives the stats of the whole catalogue', function () {
    $this->createTreatment(['name' => 'A', 'time' => 30]);
    $this->createTreatment(['name' => 'B', 'time' => 45]);
    $this->createTreatment(['name' => 'C', 'time' => 50]);
    $this->createTreatment(['name' => 'D', 'time' => null]);

    expect(treatmentsPageService()->stats())->toBe(['total' => 4, 'with_duration' => 3, 'average_minutes' => 42]);
});

it('rounds the average to the nearest minute, half up', function () {
    $this->createTreatment(['name' => 'A', 'time' => 30]);
    $this->createTreatment(['name' => 'B', 'time' => 31]);

    expect(treatmentsPageService()->stats()['average_minutes'])->toBe(31);
});

it('gives no average without treatments', function () {
    expect(treatmentsPageService()->stats())->toBe(['total' => 0, 'with_duration' => 0, 'average_minutes' => null]);
});

it('keeps the treatments of the agenda as a whole list', function () {
    for ($i = 1; $i <= 15; $i++) {
        $this->createTreatment(['name' => "Tratamiento {$i}"]);
    }

    expect(treatmentsPageService()->findAllByIdByName(null, null))->toHaveCount(15)
        ->and(treatmentsPageService()->findAllByIdByName(null, TreatmentName::fromString('Tratamiento 1')))->toHaveCount(7);
});
