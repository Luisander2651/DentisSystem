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
use Carbon\CarbonImmutable;

final readonly class GetLandingContentUseCase
{
    private const CLINIC_TIMEZONE = 'America/Mexico_City';

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
            'promotions' => $this->currentPromotions(
                $this->getPromotionsUseCase->execute(new GetPromotionsDTO(status: $dto->status)),
            ),
            'certifications' => $this->getCertificationsUseCase->execute(
                new GetCertificationsDTO(status: $dto->status),
            ),
            'testimonials' => $this->getTestimonialsUseCase->execute(
                new GetTestimonialsDTO(status: $dto->status),
            ),
        ];
    }

    /**
     * The promotions that run today on the clinic's clock, the one that ends first leading
     * (spec 026, CA2 and CA14).
     *
     * @param  array<int, PromotionEntity>  $promotions
     * @return array<int, PromotionEntity>
     */
    private function currentPromotions(array $promotions): array
    {
        $today = CarbonImmutable::now(self::CLINIC_TIMEZONE);
        $current = array_values(array_filter($promotions, fn (PromotionEntity $promotion): bool => $promotion->isCurrentOn($today)));

        usort($current, fn (PromotionEntity $first, PromotionEntity $second): int => $first->EndDate()->value <=> $second->EndDate()->value);

        return $current;
    }
}
