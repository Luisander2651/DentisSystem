<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Application\DTOs\GetLandingContentDTO;
use App\Modules\ContentManagement\Application\UseCases\GetLandingContentUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models\PromotionModel;
use Carbon\CarbonImmutable;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/*
 * Spec 026, CA2 and CA14 (TM1; A7 of /analyze 026): the landing shows only the visible promotions
 * that are current on the clinic's day, ordered by the one that ends first. "Today" is the
 * application's clock in Mexico City, so travelTo() and the ui-audit clock move it.
 */

function landingPromotion(string $name, string $start, string $end, string $status = 'visible'): void
{
    PromotionModel::query()->create([
        'name' => $name,
        'description' => "Descripción de {$name}",
        'status' => $status,
        'discount_percentage' => '10',
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

/**
 * @return list<string>
 */
function landingPromotionNames(): array
{
    $content = app(GetLandingContentUseCase::class)->execute(new GetLandingContentDTO(status: 'visible'));

    return array_map(fn (PromotionEntity $promotion): string => $promotion->Name()->value, $content['promotions']);
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00', 'America/Mexico_City'));
});

it('shows a promotion that ends today', function () {
    landingPromotion('Termina hoy', '2026-11-01', '2026-11-12');

    expect(landingPromotionNames())->toBe(['Termina hoy']);
});

it('hides a promotion that ended yesterday', function () {
    landingPromotion('Terminó ayer', '2026-11-01', '2026-11-11');

    expect(landingPromotionNames())->toBe([]);
});

it('hides a promotion that starts tomorrow', function () {
    landingPromotion('Empieza mañana', '2026-11-13', '2026-11-30');

    expect(landingPromotionNames())->toBe([]);
});

it('shows a promotion that starts today', function () {
    landingPromotion('Empieza hoy', '2026-11-12', '2026-11-30');

    expect(landingPromotionNames())->toBe(['Empieza hoy']);
});

it('orders the current promotions by the one that ends first', function () {
    landingPromotion('Termina el 20', '2026-11-01', '2026-11-20');
    landingPromotion('Termina el 15', '2026-11-01', '2026-11-15');
    landingPromotion('Termina el 30', '2026-11-01', '2026-11-30');

    expect(landingPromotionNames())->toBe(['Termina el 15', 'Termina el 20', 'Termina el 30']);
});

it('never shows a hidden promotion, even when it is current (abuse)', function () {
    landingPromotion('Oculta', '2026-11-01', '2026-11-30', 'oculto');
    landingPromotion('Visible', '2026-11-01', '2026-11-30');

    expect(landingPromotionNames())->toBe(['Visible']);
});

it('decides the day on the clinic\'s clock: 23:30 in Mexico City is already the next day in UTC', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 23:30', 'America/Mexico_City'));
    landingPromotion('Termina hoy en la clínica', '2026-11-01', '2026-11-12');

    expect(landingPromotionNames())->toBe(['Termina hoy en la clínica']);
});
