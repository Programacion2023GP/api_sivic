<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlcoholHistory extends Model
{
    protected $table = 'alcohol_case_process_history';

    protected $fillable = [
        'case_id',
        'process_id',
        'user_id',
        'step_id',
    ];
}
