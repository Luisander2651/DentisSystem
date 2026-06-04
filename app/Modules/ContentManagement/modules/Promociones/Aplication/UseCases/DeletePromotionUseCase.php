<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\DeletePromotionDTO;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Exceptions\PromotionException;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Service\PromotionsService;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;

final readonly class DeletePromotionUseCase
{
    public function __construct(
        private PromotionsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(DeletePromotionDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.promotions');

        $idVo = PromotionId::fromInt((int) $dto->id);
        $found = $this->service->getAllByNameOrIdOrStatus($idVo, null, null);

        if (empty($found)) {
            throw PromotionException::notFound($idVo);
        }

        $this->service->deleteById($idVo);
    }
}