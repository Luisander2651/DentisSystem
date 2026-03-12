<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


final class UserModel extends Authenticatable
{

    use HasApiTokens, Notifiable;

    protected $table = 'users';

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
        'role_id',
    ];

    // Relaciones
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }


}