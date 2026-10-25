<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Support;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;

/**
 * Spec 018, extracted in spec 020 (D11): who may see the whole agenda gets no narrowing; the
 * doctor only ever gets their own appointments, whatever the request asks for. Anyone else is
 * refused.
 */
final readonly class DoctorScope
{
    public static function forActor(AuthorizationServiceInterface $authorization): ?AppointmentUserId
    {
        if ($authorization->can('appointments.view')) {
            return null;
        }

        if ($authorization->can('appointments.view-own')) {
            return new AppointmentUserId($authorization->currentActorId());
        }

        $authorization->assertCan('appointments.view');

        return null;
    }
}
