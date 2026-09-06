<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\AppointmentTracking\Application\DTOs\UpdatePrescriptionDTO;
use App\Modules\AppointmentTracking\Application\Exceptions\PrescriptionApplicationException;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Exceptions\PrescriptionException;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DailyFrequency;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Dosage;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DurationDays;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Instructions;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Medication;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;

final readonly class UpdatePrescriptionUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PrescriptionService $prescriptionService,
    ) {}

    public function execute(UpdatePrescriptionDTO $dto): PrescriptionEntity
    {
        $this->authorization->assertCan('appointment-tracking.prescriptions.update');

        if (! $dto->hasAtLeastOneField()) {
            throw PrescriptionApplicationException::noInfoProvided();
        }

        $id = new PrescriptionId($dto->prescriptionId);
        $prescription = $this->prescriptionService->findById($id);

        if ($prescription === null) {
            throw PrescriptionException::notFound($id);
        }

        $prescription->update(
            medication: $dto->medication !== null ? Medication::fromString($dto->medication) : null,
            dosage: $dto->dosage !== null ? Dosage::fromString($dto->dosage) : null,
            durationDays: $dto->durationDays !== null ? DurationDays::fromInt($dto->durationDays) : null,
            dailyFrequency: $dto->dailyFrequency !== null ? DailyFrequency::fromInt($dto->dailyFrequency) : null,
            instructions: $dto->instructions !== null ? Instructions::fromNullable($dto->instructions) : null,
        );

        return $this->prescriptionService->savePrescription($prescription);
    }
}
