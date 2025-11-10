<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyView extends Model
{
    protected $table = 'penalties_latest_view';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'has_history' => 'boolean'
    ];
}
