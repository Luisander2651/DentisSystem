<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Service\TreatmentsService;

/**
 * Spec 018 (D11): who may book an appointment may list the treatments to book, without the
 * management of the catalogue (`treatments.view`) that the assistant does not hold.
 */
final readonly class GetAgendaTreatmentsUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private TreatmentsService $treatmentsService,
    ) {}

    /**
     * @return TreatmentEntity[]
     */
    public function execute(): array
    {
        $this->authorization->assertCan('agenda.selectors.view');

        return $this->treatmentsService->findAllByIdByName(null, null);
    }
}
