<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\GetPromotionsDTO;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Service\PromotionsService;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;

final readonly class GetPromotionsUseCase
{
    public function __construct(
        private PromotionsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(GetPromotionsDTO $dto): array
    {
        $this->authorizationService->assertCan('manage.promotions');

        $idVo = $dto->id ? PromotionId::fromInt((int) $dto->id) : null;
        $nameVo = $dto->name ? new PromotionName($dto->name) : null;
        $statusVo = $dto->status ? PromotionStatus::fromString($dto->status) : null;

        return $this->service->getAllByNameOrIdOrStatus($idVo, $nameVo, $statusVo);
    }
}