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
        'updated_at' => 'datetime',
    ];

    /**
     * BR-15: the model is Authenticatable and is serialised outside this module, so the
     * bcrypt hash could ride along in any response that returned the model directly.
     * UserResource never projected it, but that was one careless serialisation away from
     * leaking credentials.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
