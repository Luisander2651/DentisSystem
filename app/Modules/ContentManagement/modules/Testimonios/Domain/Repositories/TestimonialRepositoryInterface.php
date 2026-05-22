<?php

namespace App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories;

use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;

interface TestimonialRepositoryInterface
{
    public function save(TestimonialEntity $data): void;

    public function destroy(TestimonialId $id): void;

    public function findByIdAndNameAndStatus(?TestimonialId $id, ?TestimonialStatus $status, ?TestimonialDate $date): array;
}