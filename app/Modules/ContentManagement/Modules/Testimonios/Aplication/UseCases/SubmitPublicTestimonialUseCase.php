<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SubmitPublicTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Service\TestimonialsService;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialAuthor;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDescription;
use Illuminate\Support\Facades\Log;

/**
 * Spec 027: an opinion left by a visitor of the public site. It is public by design, so there is no
 * assertCan (plan, Decisiones): it always arrives hidden, with the time of the server, and the clinic
 * publishes it from "Contenido" (CA2, CA8, TM4). The log keeps the id and the consent the visitor
 * gave, never the name or the text (TM6).
 */
final readonly class SubmitPublicTestimonialUseCase
{
    public function __construct(
        private TestimonialsService $service,
    ) {}

    public function execute(SubmitPublicTestimonialDTO $dto): int
    {
        $testimonial = TestimonialEntity::create(
            author: new TestimonialAuthor($dto->author),
            description: new TestimonialDescription($dto->description),
        );
        $testimonial->hidden();

        $id = $this->service->save($testimonial);

        Log::info('public_testimonial.received', ['testimonial_id' => $id, 'consent' => true]);

        return $id;
    }
}
