<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class PatientModel extends Authenticatable
{

    use HasApiTokens, Notifiable;

    protected $table = 'patients';

    public $incrementing = false;
    public $keyType = 'string';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'password',
        'status',
        'role',
    ];

    // Relaciones
    public function addresses(): HasMany
    {
        return $this->hasMany(AddressesModel::class, 'patient_id', 'id');
    }

    public function contactInfo(): HasMany
    {
        return $this->hasMany(ContactInfoModel::class, 'patient_id', 'id');
    }

    public function medicalData(): HasMany
    {
        return $this->hasMany(MedicalDataModel::class, 'patient_id', 'id');
    }
}