<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MedicalDataModel extends Model
{
    protected $table = 'medical_data';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'allergies' => 'array',
        'medications' => 'array',
        'last_dentist_visit' => 'array',
    ];

    protected $fillable = [
        'patient_id',
        'blood_type',
        'allergies',
        'medications',
        'last_dentist_visit',
    ];

    // Relaciones
    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientModel::class, 'patient_id', 'id');
    }
}