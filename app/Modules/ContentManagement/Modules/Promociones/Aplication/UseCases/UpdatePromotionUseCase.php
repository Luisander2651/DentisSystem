<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\UpdatePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Service\PromotionsService;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;

final readonly class UpdatePromotionUseCase
{
    public function __construct(
        private PromotionsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(UpdatePromotionDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.promotions');

        $idVo = PromotionId::fromInt((int) $dto->id);
        $found = $this->service->getAllByNameOrIdOrStatus($idVo, null, null);

        if (empty($found)) {
            throw PromotionException::notFound($idVo);
        }

        $entity = $found[0];

        $entity->update(
            name: $dto->name,
            description: $dto->description,
            discountPercentage: $dto->discountPercentage,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            status: $dto->status,
        );

        $this->service->save($entity);
    }
}