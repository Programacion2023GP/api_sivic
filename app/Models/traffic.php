<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Traffic extends Model
{
    protected $table = 'traffic';

    protected $fillable = [
        'citizen_name',
        'age',
        'rank',
        'plate_number',
        'vehicle_brand',
        'time',
        'location',
        'created_by',
        'person_oficial',
        'image_traffic',
        'active' // Asumiendo que también tienes un campo active
    ];

    protected $attributes = [
        'active' => 1
    ];
}
