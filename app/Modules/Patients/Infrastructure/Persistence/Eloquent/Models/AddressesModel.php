<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AddressesModel extends Model
{
    protected $table = 'addresses';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'patient_id',
        'street',
        'city',
        'state',
        'postal_code',
    ];

    // Relaciones
    public function patient(): HasOne
    {
        return $this->hasOne(PatientsModel::class, 'id', 'patient_id');
    }
}