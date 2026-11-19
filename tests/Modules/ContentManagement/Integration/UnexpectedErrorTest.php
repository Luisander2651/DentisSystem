<?php

declare(strict_types=1);

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories\CertificationRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories\PromotionRepositoryInterface;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Repositories\TestimonialRepositoryInterface;
use Tests\Modules\ContentManagement\Integration\ContentManagementIntegrationTestCase;

uses(ContentManagementIntegrationTestCase::class);

/**
 * Spec 014, CA12: an unexpected error while listing the content of the public site from the
 * administration panel answers a generic 500, without the technical message of the failure.
 */
it('answers a generic 500 when listing content fails unexpectedly', function (string $url, string $repository, string $method) {
    $this->actingAsAdmin();
    $this->mock($repository)->shouldReceive($method)->andThrow(new RuntimeException('detalle interno'));

    $this->getJson($url)
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
})->with([
    'certifications' => ['/api/v1/certifications', CertificationRepositoryInterface::class, 'findPage'],
    'certifications counts' => ['/api/v1/certifications', CertificationRepositoryInterface::class, 'countByStatus'],
    'gallery' => ['/api/v1/gallery-images', GalleryImageRepositoryInterface::class, 'findPage'],
    'gallery counts' => ['/api/v1/gallery-images', GalleryImageRepositoryInterface::class, 'countByStatus'],
    'promotions' => ['/api/v1/promotions', PromotionRepositoryInterface::class, 'findPage'],
    'promotions counts' => ['/api/v1/promotions', PromotionRepositoryInterface::class, 'countByStatus'],
    'testimonials' => ['/api/v1/testimonials', TestimonialRepositoryInterface::class, 'findPage'],
    'testimonials counts' => ['/api/v1/testimonials', TestimonialRepositoryInterface::class, 'countByStatus'],
]);
