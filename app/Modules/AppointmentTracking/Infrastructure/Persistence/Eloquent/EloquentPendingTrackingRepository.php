<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\EloquentAppointmentRepository;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Spec 020 (CA7, TM8): one limited query for the page and a count() with the same filters.
 */
class EloquentPendingTrackingRepository implements PendingTrackingRepositoryInterface
{
    public function __construct(
        private readonly EloquentAppointmentRepository $appointments,
    ) {}

    public function findCompletedWithoutTracking(?string $userId, int $limit): array
    {
        $appointments = (new AppointmentModel)->getTable();
        $tracking = (new AppointmentTrackingModel)->getTable();

        $query = AppointmentModel::query()
            ->where('status', AppointmentStatus::completed()->value)
            ->whereNotExists(fn (QueryBuilder $exists) => $exists
                ->selectRaw('1')
                ->from($tracking)
                ->whereColumn("{$tracking}.appointment_id", "{$appointments}.id"))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId));

        $total = (clone $query)->count();

        $items = $query
            ->with([
                'user:id,first_name,last_name',
                'patient:id,first_name,last_name',
                'treatment:id,name,time',
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->limit($limit)
            ->get()
            ->map(fn (AppointmentModel $appointment): AppointmentEntity => $this->appointments->mapToDomain($appointment))
            ->all();

        return ['items' => $items, 'total' => $total];
    }
}
