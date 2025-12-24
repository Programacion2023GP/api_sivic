<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryPenalty extends Model
{
    protected $table = 'historypenalties';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'has_history' => 'boolean'
    ];
}
