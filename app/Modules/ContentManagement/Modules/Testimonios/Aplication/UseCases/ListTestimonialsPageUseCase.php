<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Entities\TestimonialEntity;
use App\Modules\ContentManagement\Modules\Testimonios\Domain\Service\TestimonialsService;

/**
 * Spec 028 (CA5, CA14; P5, A21): one page of the testimonials of the panel with the counts by status of
 * the whole list, only for whoever manages them.
 */
final readonly class ListTestimonialsPageUseCase
{
    public function __construct(
        private TestimonialsService $service,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<TestimonialEntity>, counts: array{total: int, visible: int, hidden: int}}
     */
    public function execute(PageRequest $request): array
    {
        $this->authorization->assertCan('manage.testimonials');

        return [
            'page' => $this->service->findPage($request),
            'counts' => $this->service->countByStatus(),
        ];
    }
}
