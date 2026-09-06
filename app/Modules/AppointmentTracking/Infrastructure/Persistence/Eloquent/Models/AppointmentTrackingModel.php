<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AppointmentTrackingModel extends Model
{
    protected $table = 'appointment_tracking';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $keyType = 'string';

    public $timestamps = true;

    protected $casts = [
        'symptoms' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'reason',
        'symptoms',
        'diagnosis',
        'procedure_performed',
        'observations',
        'recommendations',
        'appointment_id',
    ];

    // Relaciones
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(AppointmentModel::class, 'appointment_id', 'id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(AppointmentTrackingPrescriptionModel::class, 'appointment_tracking_id', 'id');
    }
}
