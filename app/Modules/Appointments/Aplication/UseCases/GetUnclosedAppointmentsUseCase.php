<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Modules\Appointments\Aplication\Support\DoctorScope;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;

/**
 * Spec 020 (CA4, TM1, TM8): the appointments of earlier days still open, the whole clinic or only
 * the doctor's own, and the total of all. Spec 028 (CA2): one page of ten at a time.
 */
final readonly class GetUnclosedAppointmentsUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return Page<AppointmentEntity>
     */
    public function execute(int $page = 1): Page
    {
        $userId = DoctorScope::forActor($this->authorization);

        return $this->appointmentsService->findUnclosed(AppointmentDate::fromString(now()->toDateString()), $userId, $page);
    }
}
