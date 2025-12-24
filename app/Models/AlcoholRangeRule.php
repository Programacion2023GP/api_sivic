<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlcoholRangeRule extends Model
{
    protected $table = 'alcohol_range_rules';

    protected $fillable = [
        'min_value',
        'max_value',
        'active'
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación: Procesos asociados a esta regla (Many to Many)
     */
    public function processes()
    {
        return $this->belongsToMany(
            Process::class,
            'alcohol_range_rules_process',
            'rule_id',
            'process_id'
        )
            ->withPivot('active')
            ->withTimestamps()
            ->orderBy('orden');
    }
    // En el modelo AlcoholCase
    public function nextProcess()
    {
        // 1. Obtener la regla aplicable
        $rule = $this->applicableRule();

        if (!$rule) {
            return null;
        }

        // 2. Obtener todos los procesos activos de la regla ordenados
        $processes = $rule->processes()
            ->wherePivot('active', true)
            ->orderBy('orden')
            ->get();

        if ($processes->isEmpty()) {
            return null;
        }

        // 3. Si no hay proceso actual, retornar el primero
        if (!$this->current_process_id) {
            return $processes->first();
        }

        // 4. Buscar la posición del proceso actual
        $currentIndex = $processes->search(function ($process) {
            return $process->id === $this->current_process_id;
        });

        // 5. Si no se encuentra el proceso actual
        if ($currentIndex === false) {
            // Tal vez el proceso fue desactivado, retornar el primero
            return $processes->first();
        }

        // 6. Si es el último proceso, retornar null
        if ($currentIndex === $processes->count() - 1) {
            return null;
        }

        // 7. Retornar el siguiente proceso
        return $processes[$currentIndex + 1];
    }
    /**
     * Scope: Reglas activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Buscar regla por nivel de alcohol
     */
    public function scopeForAlcoholLevel($query, $level)
    {
        return $query->where('active', true)
            ->where('min_value', '<=', $level)
            ->where(function ($q) use ($level) {
                $q->whereNull('max_value')
                    ->orWhere('max_value', '>=', $level);
            });
    }

    /**
     * Verificar si un nivel de alcohol está en este rango
     */
    public function containsLevel($alcoholLevel)
    {
        if ($alcoholLevel < $this->min_value) {
            return false;
        }

        if ($this->max_value === null) {
            return true;
        }

        return $alcoholLevel <= $this->max_value;
    }

    /**
     * Obtener descripción legible del rango
     */
    public function getRangeDescriptionAttribute()
    {
        if ($this->max_value === null) {
            return "{$this->min_value}% o más";
        }

        return "{$this->min_value}% - {$this->max_value}%";
    }
}
