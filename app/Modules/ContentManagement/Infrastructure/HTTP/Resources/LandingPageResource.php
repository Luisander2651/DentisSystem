<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Infrastructure\HTTP\Resources;

use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Resources\CertificationResource;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Resources\PromotionResource;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Resources\TestimonialResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LandingPageResource extends JsonResource
{
    /**
     * Transform the landing content into the shape the view consumes.
     *
     * @param  Request  $request
     * @return array{
     *     promotions: array<int, array<string, mixed>>,
     *     certifications: array<int, array<string, mixed>>,
     *     testimonials: array<int, array<string, mixed>>,
     * }
     */
    public function toArray($request): array
    {
        return [
            'promotions' => $this->mapCollection(PromotionResource::class, 'promotions', $request),
            'certifications' => $this->mapCollection(CertificationResource::class, 'certifications', $request),
            'testimonials' => $this->mapCollection(TestimonialResource::class, 'testimonials', $request),
        ];
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @return array<int, array<string, mixed>>
     */
    private function mapCollection(string $resourceClass, string $key, Request $request): array
    {
        $items = $this->resource[$key] ?? [];

        return array_map(
            static fn ($item): array => (new $resourceClass($item))->toArray($request),
            $items,
        );
    }
}
