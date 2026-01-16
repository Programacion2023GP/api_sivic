<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'courts';
    protected $fillable = [
        'date',
        'referring_agency',
        'detainee_name',
        'detention_reason',
        'entry_time',
        'exit_datetime',
        'exit_reason',
        'fine_amount',
        'created_at',
        'updated_at',
        'active',
        'created_by',
        'image_court',
    ];
}
