<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicsecurities extends Model
{
    use HasFactory;
    protected $table = 'public_securities';

    protected $fillable = [
        'detainee_name',
        'officer_name',
        'patrol_unit_number',
        'detention_reason',
        'date',
        'time',
        'age',
        'created_by',
        'location',
        'active',
    ];
}
