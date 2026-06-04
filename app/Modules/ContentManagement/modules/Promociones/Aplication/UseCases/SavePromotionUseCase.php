<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\SavePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Service\PromotionsService;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\DiscountPercentage;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\EndDate;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionDescription;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\StartDate;

final readonly class SavePromotionUseCase
{
    public function __construct(
        private PromotionsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(SavePromotionDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.promotions');

        $promotion = PromotionEntity::create(
            name: new PromotionName($dto->name),
            description: new PromotionDescription($dto->description),
            discountPercentage: new DiscountPercentage($dto->discountPercentage),
            startDate: new StartDate($dto->startDate),
            endDate: new EndDate($dto->endDate),
        );

        if ($dto->status === 'oculto') {
            $promotion->deactivate();
        }

        $this->service->save($promotion);
    }
}