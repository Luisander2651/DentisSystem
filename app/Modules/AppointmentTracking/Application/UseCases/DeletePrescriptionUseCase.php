<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\AppointmentTracking\Application\DTOs\DeletePrescriptionDTO;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;

final readonly class DeletePrescriptionUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PrescriptionService $prescriptionService,
    ) {}

    public function execute(DeletePrescriptionDTO $dto): void
    {
        $this->authorization->assertCan('appointment-tracking.prescriptions.delete');

        $id = new PrescriptionId($dto->prescriptionId);
        $this->prescriptionService->deletePrescription($id);
    }
}
