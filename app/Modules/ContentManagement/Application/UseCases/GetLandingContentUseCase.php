<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Application\UseCases;

use App\Modules\ContentManagement\Application\DTOs\GetLandingContentDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\GetCertificationsDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases\GetCertificationsUseCase;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs\GetPromotionsDTO;
use App\Modules\ContentManagement\Modules\Promociones\Aplication\UseCases\GetPromotionsUseCase;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs\GetTestimonialsDTO;
use App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases\GetTestimonialsUseCase;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;

final readonly class GetLandingContentUseCase
{
    public function __construct(
        private GetPromotionsUseCase $getPromotionsUseCase,
        private GetCertificationsUseCase $getCertificationsUseCase,
        private GetTestimonialsUseCase $getTestimonialsUseCase,
    ) {}

    /**
     * @return array{
     *     promotions: array<int, PromotionEntity>,
     *     certifications: array<int, CertificationEntity>,
     *     testimonials: array<int, TestimonialEntity>,
     * }
     */
    public function execute(GetLandingContentDTO $dto): array
    {
        return [
            'promotions' => $this->getPromotionsUseCase->execute(
                new GetPromotionsDTO(status: $dto->status),
            ),
            'certifications' => $this->getCertificationsUseCase->execute(
                new GetCertificationsDTO(status: $dto->status),
            ),
            'testimonials' => $this->getTestimonialsUseCase->execute(
                new GetTestimonialsDTO(status: $dto->status),
            ),
        ];
    }
}
