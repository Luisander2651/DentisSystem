<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Service;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;

final readonly class TestimonialsService
{
    public function __construct(
        private TestimonialRepositoryInterface $repository,
    ) {}

    /** Saves the testimonial and returns the id of its row. */
    public function save(TestimonialEntity $testimonial): int
    {
        return $this->repository->save($testimonial);
    }

    public function deleteById(TestimonialId $id): void
    {
        $this->repository->destroy($id);
    }

    public function getAllByIdOrStatusOrDate(?TestimonialId $id, ?TestimonialStatus $status, ?TestimonialDate $date): array
    {
        return $this->repository->findByIdAndNameAndStatus(
            id: $id,
            status: $status,
            date: $date,
        );
    }

    /**
     * @return Page<TestimonialEntity>
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
