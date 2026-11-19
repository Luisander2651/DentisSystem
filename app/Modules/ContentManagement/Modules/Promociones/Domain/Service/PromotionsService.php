<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\Service;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;

final readonly class PromotionsService
{
    public function __construct(
        private PromotionRepositoryInterface $repository,
    ) {}

    public function save(PromotionEntity $promotion): void
    {
        $this->repository->save($promotion);
    }

    public function deleteById(PromotionId $id): void
    {
        $this->repository->destroy($id);
    }

    public function getAllByNameOrIdOrStatus(?PromotionId $id, ?PromotionName $name, ?PromotionStatus $status): array
    {
        return $this->repository->findByIdAndName(
            id: $id,
            name: $name,
            status: $status,
        );
    }

    /**
     * @return Page<PromotionEntity>
     */
    public function findPage(PageRequest $request): Page
    {
        return $this->repository->findPage($request);
    }

    /**
     * @return array{total: int, visible: int, hidden: int}
     */
    public function countByStatus(): array
    {
        return $this->repository->countByStatus();
    }
}
