<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AppointmentModel extends Model
{
    protected $table = 'appointments';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'date',
        'time',
        'whatsapp_reminder',
        'status',
        'treatment_id',
        'user_id',
        'patient_id'
    ];

    // Relaciones
    public function user(): HasOne
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }

    public function treatment(): HasOne
    {
        return $this->hasOne(TreatmentModel::class, 'id', 'treatment_id');
    }

    public function patient(): HasOne
    {
        return $this->hasOne(PatientModel::class, 'id', 'patient_id');
    }
}