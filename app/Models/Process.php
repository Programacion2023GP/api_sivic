<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Process extends Model
{
    protected $table = 'processes';

    protected $fillable = [
        'model_class',
        'orden',
        'active'
    ];

    protected $casts = [
        'orden' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación: Reglas que usan este proceso (Many to Many)
     */
    public function rules()
    {
        return $this->belongsToMany(
            AlcoholRangeRule::class,
            'alcohol_range_rules_process',
            'process_id',
            'rule_id'
        )
            ->withPivot('active')
            ->withTimestamps();
    }

    /**
     * Relación: Casos actualmente en este proceso
     */
    public function currentCases()
    {
        return $this->hasMany(AlcoholCase::class, 'current_process_id');
    }

    /**
     * Scope: Procesos activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Ordenados por campo 'orden'
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Obtener el nombre de la clase sin namespace
     */
    public function getClassNameAttribute()
    {
        if (!$this->model_class) {
            return null;
        }

        $parts = explode('\\', $this->model_class);
        return end($parts);
    }

    /**
     * Verificar si la clase del modelo existe
     */
    public function modelClassExists()
    {
        return $this->model_class && class_exists($this->model_class);
    }
}