<?php

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;

interface TestimonialRepositoryInterface
{
    /** Saves the testimonial and returns the id of its row. */
    public function save(TestimonialEntity $data): int;

    public function destroy(TestimonialId $id): void;

    public function findByIdAndNameAndStatus(?TestimonialId $id, ?TestimonialStatus $status, ?TestimonialDate $date): array;

    /**
     * Spec 028 (CA7): one page of the list of the panel, newest first.
     *
     * @return Page<TestimonialEntity>
     */
    public function findPage(PageRequest $request): Page;

    /**
     * Spec 028 (CA5): how many items the whole list holds, by status.
     *
     * @return array{total: int, visible: int, hidden: int}
     */
    public function countByStatus(): array;
}
