<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlcoholCase extends Model
{
    protected $table = 'alcohol_cases';

    protected $fillable = [
        'alcohol_level',
        'current_process_id',
        'active',
        'name',
        'city',
        'cp',
        'time',
        'date',
        'plate_number',
        'age',
        'long',
        'lat',
        'residence_folio',
        'finish',
       
    ];
     protected $casts = [
        'alcohol_level' => 'decimal:2',
        'active' => 'boolean',
        'requires_confirmation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación: Proceso actual del caso
     */
    public function currentProcess()
    {
        return $this->belongsTo(Process::class, 'current_process_id');
    }

    /**
     * Obtener la regla aplicable para este caso
     */
    public function applicableRule()
    {
        return AlcoholRangeRule::where('active', true)
            ->where('min_value', '<=', $this->alcohol_level)
            ->where(function ($query) {
                $query->whereNull('max_value')
                    ->orWhere('max_value', '>=', $this->alcohol_level);
            })
            ->first();
    }

    /**
     * Scope: Casos activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Casos en un proceso específico
     */
    public function scopeInProcess($query, $processId)
    {
        return $query->where('current_process_id', $processId);
    }

    /**
     * Scope: Casos por rango de alcohol
     */
    public function scopeByAlcoholRange($query, $min, $max = null)
    {
        $query->where('alcohol_level', '>=', $min);

        if ($max !== null) {
            $query->where('alcohol_level', '<=', $max);
        }

        return $query;
    }
}
