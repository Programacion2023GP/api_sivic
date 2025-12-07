<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlcoholRangeRule extends Model
{
    use HasFactory;

    protected $table = 'alcohol_range_rules';

    protected $fillable = [
        'min_value',
        'max_value',
        'active',
    ];

    /**
     * Relación con procesos (Penalty, PublicSecurity, Traffic, Court)
     */
    public function processes()
    {
        return $this->belongsToMany(
            Process::class,
            'alcohol_range_rules_process', // tabla pivote correcta
            'rule_id',   // FK en la pivote
            'process_id' // FK en la pivote
        )->withPivot(['active'])
            ->withTimestamps();
    }
}
