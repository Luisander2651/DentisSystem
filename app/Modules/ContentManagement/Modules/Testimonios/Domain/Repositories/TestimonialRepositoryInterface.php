<?php

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories;

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
}
