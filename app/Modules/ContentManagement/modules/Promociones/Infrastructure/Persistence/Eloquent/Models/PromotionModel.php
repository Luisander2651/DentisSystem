<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class PromotionModel extends Model
{
    protected $table = 'promotions';

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
        'discount_percentage',
        'start_date',
        'end_date',
    ];
}
