<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentTrackingPrescriptionModel extends Model
{
    protected $table = 'appointment_tracking_prescriptions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $keyType = 'string';

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'medication',
        'dosage',
        'duration_days',
        'daily_frequency',
        'instructions',
        'appointment_tracking_id',
    ];

    // Relaciones
    public function appointmentTracking(): BelongsTo
    {
        return $this->belongsTo(AppointmentTrackingModel::class, 'appointment_tracking_id', 'id');
    }
}
