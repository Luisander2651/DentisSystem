<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\Support\DoctorScope;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;

/**
 * Spec 020 (CA4, TM1, TM8): the appointments of earlier days still open, the whole clinic or only
 * the doctor's own, at most AppointmentPage::LIMIT and the total of all.
 */
final readonly class GetUnclosedAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    public function execute(): AppointmentPage
    {
        $userId = DoctorScope::forActor($this->authorization);

        return $this->appointmentsService->findUnclosed(AppointmentDate::fromString(now()->toDateString()), $userId);
    }
}
