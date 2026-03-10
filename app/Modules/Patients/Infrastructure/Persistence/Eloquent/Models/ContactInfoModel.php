<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContactInfoModel extends Model
{
    protected $table = 'contact_info';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'patient_id',
        'phone_number',
        'emergency_contact',
        'email',
    ];

    // Relaciones
    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientModel::class, 'patient_id', 'id');
    }
}