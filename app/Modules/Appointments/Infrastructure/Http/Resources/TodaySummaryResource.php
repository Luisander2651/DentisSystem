<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Resources;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TodayAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spec 020 (CA1, CA2, CA3): the contract of GET /agenda/today-summary - the appointments of
 * today, the counters of the day, the next one and the ones after it.
 *
 * @property TodayAgenda $resource
 */
final class TodaySummaryResource extends JsonResource
{
    private CarbonImmutable $now;

    public static function at(TodayAgenda $agenda, CarbonImmutable $now): self
    {
        $resource = new self($agenda);
        $resource->now = $now;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $agenda = $this->resource;
        $next = $agenda->next;

        return [
            'date' => $this->now->toDateString(),
            'now' => $this->now->format('H:i'),
            'appointments' => array_map(
                fn (AppointmentEntity $appointment): array => DashboardAppointmentResource::ofToday($appointment, $agenda)->toArray($request),
                $agenda->appointments,
            ),
            'counts' => [
                'total' => $agenda->total,
                'completed' => $agenda->completed,
                'pending' => $agenda->pending,
                'cancelled' => $agenda->cancelled,
            ],
            'next' => $next === null ? null : [
                'id' => $next->Id()->value,
                'time' => substr($next->Time()->value, 0, 5),
                'status' => $next->Status()->value,
                'patient_name' => $next->PatientName()?->full(),
                'treatment_name' => $next->TreatmentName()?->value,
                'doctor_name' => $next->UserName()?->full(),
                'can' => DashboardAppointmentResource::can($next),
            ],
            'after' => array_map(fn (AppointmentEntity $appointment): array => [
                'time' => substr($appointment->Time()->value, 0, 5),
                'patient_name' => $appointment->PatientName()?->full(),
            ], $agenda->after),
            'after_more' => $agenda->afterMore,
        ];
    }
}
