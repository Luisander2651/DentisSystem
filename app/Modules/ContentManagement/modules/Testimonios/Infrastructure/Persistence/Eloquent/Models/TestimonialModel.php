<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class TestimonialModel extends Model
{
    protected $table = 'testimonials';

    public $incrementing = true;
    public $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'author',
        'description',
        'status',
        'date',
    ];
}
