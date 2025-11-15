<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyPreloadData extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'oficial_payroll',
        'person_oficial',
        'civil_protection',
        'command_vehicle',
        'command_troops',
        'group',
        'person_contraloria',
        'command_details',
        'filter_supervisor',
        'doctor_id',
        'init_date',
        'final_date',
        'user_id',
        'active',
        'created_at',
        'updated_at',
    ];

    public function histories()
    {
        return $this->hasMany(Penalty::class);
    }
}