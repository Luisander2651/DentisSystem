<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;

/**
 * Spec 028 (CA5, CA14; P5, A21): one page of the certifications of the panel with the counts by status of
 * the whole list, only for whoever manages them.
 */
final readonly class ListCertificationsPageUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<CertificationEntity>, counts: array{total: int, visible: int, hidden: int}}
     */
    public function execute(PageRequest $request): array
    {
        $this->authorization->assertCan('manage.certifications');

        return [
            'page' => $this->service->findPage($request),
            'counts' => $this->service->countByStatus(),
        ];
    }
}
