<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentModel extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';

    public $incrementing = false;
    public $keyType = 'string';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'id',
        'date',
        'time',
        'whatsapp_reminder',
        'status',
        'treatment_id',
        'user_id',
        'patient_id'
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(TreatmentModel::class, 'treatment_id', 'id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientModel::class, 'patient_id', 'id');
    }
}