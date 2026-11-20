<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Service\PromotionsService;

/**
 * Spec 028 (CA5, CA14; P5, A21): one page of the promotions of the panel with the counts by status of
 * the whole list, only for whoever manages them.
 */
final readonly class ListPromotionsPageUseCase
{
    public function __construct(
        private PromotionsService $service,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<PromotionEntity>, counts: array{total: int, visible: int, hidden: int}}
     */
    public function execute(PageRequest $request): array
    {
        $this->authorization->assertCan('manage.promotions');

        return [
            'page' => $this->service->findPage($request),
            'counts' => $this->service->countByStatus(),
        ];
    }
}
