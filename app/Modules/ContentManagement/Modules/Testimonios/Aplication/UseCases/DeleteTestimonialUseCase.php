<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\DeleteTestimonialDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Exceptions\TestimonialException;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Service\TestimonialsService;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;

final readonly class DeleteTestimonialUseCase
{
    public function __construct(
        private TestimonialsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(DeleteTestimonialDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.testimonials');

        $idVo = TestimonialId::fromInt((int) $dto->id);
        $found = $this->service->getAllByIdOrStatusOrDate($idVo, null, null);

        if (empty($found)) {
            throw TestimonialException::notFound($idVo);
        }

        $this->service->deleteById($idVo);
    }
}