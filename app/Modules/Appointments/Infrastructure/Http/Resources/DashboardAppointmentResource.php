<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Resources;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TodayAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 020 (CA1, CA4, CA5, CA6, TM6): an appointment of the dashboard with fixed fields - no
 * clinical nor contact data. `can` says what the actor may do with it; the server checks it
 * again on every operation (P13). Today's carry `is_past` and `is_next`; the unclosed ones,
 * the label of their day.
 *
 * @property AppointmentEntity $resource
 */
final class DashboardAppointmentResource extends JsonResource
{
    private const WEEKDAYS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    private ?TodayAgenda $agenda = null;

    private ?CarbonImmutable $today = null;

    public static function ofToday(AppointmentEntity $appointment, TodayAgenda $agenda): self
    {
        $resource = new self($appointment);
        $resource->agenda = $agenda;

        return $resource;
    }

    public static function ofEarlierDay(AppointmentEntity $appointment, CarbonImmutable $today): self
    {
        $resource = new self($appointment);
        $resource->today = $today;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $appointment = $this->resource;

        $fields = [
            'id' => $appointment->Id()->value,
            'date' => $appointment->Date()->value,
            'time' => substr($appointment->Time()->value, 0, 5),
            'status' => $appointment->Status()->value,
            'patient_name' => $appointment->PatientName()?->full(),
            'treatment_name' => $appointment->TreatmentName()?->value,
            'doctor_name' => $appointment->UserName()?->full(),
        ];

        if ($this->agenda !== null) {
            $fields['is_past'] = $this->agenda->isPast($appointment);
            $fields['is_next'] = $this->agenda->isNext($appointment);
        }

        if ($this->today !== null) {
            $fields['day_label'] = self::dayLabel(CarbonImmutable::parse($appointment->Date()->value), $this->today);
        }

        $fields['can'] = self::can($appointment);

        return $fields;
    }

    /**
     * @return array{complete: bool, cancel: bool}
     */
    public static function can(AppointmentEntity $appointment): array
    {
        $authorization = app(AuthorizationServiceInterface::class);
        $isOpen = $appointment->Status()->isAssigned() || $appointment->Status()->isRescheduled();
        $completesAny = $authorization->can('appointment-tracking.create');
        $completesOwn = $authorization->can('appointment-tracking.create-own')
            && $appointment->UserId()->value === $authorization->currentActorId();

        return [
            'complete' => $isOpen && ($completesAny || $completesOwn),
            'cancel' => $isOpen && $authorization->can('appointments.update'),
        ];
    }

    /**
     * "Ayer · miércoles 21" for yesterday, "Martes 20" for any other day.
     */
    public static function dayLabel(CarbonImmutable $day, CarbonImmutable $today): string
    {
        $weekday = self::WEEKDAYS[$day->dayOfWeek];

        if ($day->isSameDay($today->subDay())) {
            return 'Ayer · '.$weekday.' '.$day->day;
        }

        return mb_strtoupper(mb_substr($weekday, 0, 1)).mb_substr($weekday, 1).' '.$day->day;
    }
}
