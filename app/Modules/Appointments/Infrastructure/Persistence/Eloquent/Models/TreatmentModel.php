<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TreatmentModel extends Model
{
    protected $table = 'treatments';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'name',
        'description'
    ];

    // Relaciones
    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class, 'treatment_id', 'id');
    }
}