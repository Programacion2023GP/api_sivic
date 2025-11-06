<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyHistory extends Model
{
    use HasFactory;

    protected $fillable = ['penalty_id', 'data', 'modified_by', 'action'];

    protected $casts = [
        'data' => 'array', // decodifica JSON automáticamente
    ];

    public function penalty()
    {
        return $this->belongsTo(Penalty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
