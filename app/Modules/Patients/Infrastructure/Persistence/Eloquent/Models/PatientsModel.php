<?php

declare(strict_types=1);

namespace App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class PatientsModel extends Model
{
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
}