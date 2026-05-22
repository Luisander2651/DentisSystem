<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Service;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;

final readonly class TestimonialsService
{
    public function __construct(
        private TestimonialRepositoryInterface $repository,
    ) {
    }

    public function save(TestimonialEntity $testimonial): void
    {
        $this->repository->save($testimonial);
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
}
