<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RoleModel extends Model
{
    protected $table = 'roles';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'name',
        'description',
    ];

    // Relaciones
    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'role_id');
    }
}