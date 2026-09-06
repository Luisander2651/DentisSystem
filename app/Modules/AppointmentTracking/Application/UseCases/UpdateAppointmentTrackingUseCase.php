<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\AppointmentTracking\Application\DTOs\UpdateAppointmentTrackingDTO;
use App\Modules\AppointmentTracking\Application\Exceptions\AppointmentTrackingApplicationException;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Diagnosis;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Observations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\ProcedurePerformed;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Reason;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Recommendations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Symptoms;

final readonly class UpdateAppointmentTrackingUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentTrackingService $appointmentTrackingService,
    ) {}

    public function execute(UpdateAppointmentTrackingDTO $dto): AppointmentTrackingEntity
    {
        $this->authorization->assertCan('appointment-tracking.update');

        if (! $dto->hasAtLeastOneField()) {
            throw AppointmentTrackingApplicationException::noInfoProvided();
        }

        $id = new AppointmentTrackingId($dto->appointmentTrackingId);
        $tracking = $this->appointmentTrackingService->findById($id);

        if ($tracking === null) {
            throw AppointmentTrackingException::notFound($id);
        }

        $tracking->update(
            reason: $dto->reason !== null ? Reason::fromString($dto->reason) : null,
            symptoms: $dto->symptoms !== null ? Symptoms::fromArray($dto->symptoms) : null,
            diagnosis: $dto->diagnosis !== null ? Diagnosis::fromString($dto->diagnosis) : null,
            procedurePerformed: $dto->procedurePerformed !== null ? ProcedurePerformed::fromString($dto->procedurePerformed) : null,
            observations: $dto->observations !== null ? Observations::fromNullable($dto->observations) : null,
            recommendations: $dto->recommendations !== null ? Recommendations::fromNullable($dto->recommendations) : null,
        );

        return $this->appointmentTrackingService->saveAppointmentTracking($tracking);
    }
}
