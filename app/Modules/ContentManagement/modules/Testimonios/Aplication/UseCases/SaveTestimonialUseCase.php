<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\SaveTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Service\TestimonialsService;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialAuthor;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDate;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialDescription;

final readonly class SaveTestimonialUseCase
{
    public function __construct(
        private TestimonialsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(SaveTestimonialDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.testimonials');

        $testimonial = TestimonialEntity::create(
            author: new TestimonialAuthor($dto->author),
            description: new TestimonialDescription($dto->description),
        );

        if ($dto->status === 'oculto') {
            $testimonial->hidden();
        }

        $this->service->save($testimonial);
    }
}