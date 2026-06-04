<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\GetTestimonialsDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Service\TestimonialsService;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialId;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\ValueObjects\TestimonialStatus;

final readonly class GetTestimonialsUseCase
{
    public function __construct(
        private TestimonialsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(GetTestimonialsDTO $dto): array
    {
        $this->authorizationService->assertCan('manage.testimonials');

        $idVo = $dto->id ? TestimonialId::fromInt((int) $dto->id) : null;
        $statusVo = $dto->status ? TestimonialStatus::fromString($dto->status) : null;

        return $this->service->getAllByIdOrStatusOrDate($idVo, $statusVo, null);
    }
}