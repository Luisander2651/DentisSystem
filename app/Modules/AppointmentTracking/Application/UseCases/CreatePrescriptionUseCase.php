<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\AppointmentTracking\Application\DTOs\CreatePrescriptionDTO;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DailyFrequency;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Dosage;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DurationDays;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Instructions;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Medication;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;

final readonly class CreatePrescriptionUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentTrackingService $appointmentTrackingService,
        private PrescriptionService $prescriptionService,
    ) {}

    public function execute(CreatePrescriptionDTO $dto): PrescriptionEntity
    {
        $this->authorization->assertCan('appointment-tracking.prescriptions.create');

        $appointmentTrackingId = new AppointmentTrackingId($dto->appointmentTrackingId);
        $tracking = $this->appointmentTrackingService->findById($appointmentTrackingId);

        if ($tracking === null) {
            throw AppointmentTrackingException::notFound($appointmentTrackingId);
        }

        $prescription = PrescriptionEntity::create(
            appointmentTrackingId: new PrescriptionAppointmentTrackingId($tracking->Id()->value),
            medication: Medication::fromString($dto->medication),
            dosage: Dosage::fromString($dto->dosage),
            durationDays: DurationDays::fromInt($dto->durationDays),
            dailyFrequency: DailyFrequency::fromInt($dto->dailyFrequency),
            instructions: Instructions::fromNullable($dto->instructions),
        );

        return $this->prescriptionService->savePrescription($prescription);
    }
}
