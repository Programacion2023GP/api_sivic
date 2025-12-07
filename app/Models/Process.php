<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Process extends Model
{
    use HasFactory;

    protected $table = 'processes';

    protected $fillable = [
        'name',
        'orden',
        'active',
    ];

    public function rules()
    {
        return $this->belongsToMany(
            AlcoholRangeRule::class,
            'alcohol_range_rules_process',
            'process_id',
            'rule_id'
        )->withPivot(['active'])
            ->withTimestamps();
    }
}
