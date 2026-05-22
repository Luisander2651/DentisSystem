<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class CertificationModel extends Model
{
    protected $table = 'certifications';

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
        'status',
        'date',
        'image_url',
    ];
}
