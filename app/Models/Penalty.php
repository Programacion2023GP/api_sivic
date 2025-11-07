<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'time',
        'date',
        'images_evidences',
        'person_contraloria',
        'oficial_payroll',
        'person_oficial',
        'vehicle_service_type',
        'alcohol_concentration',
        'group',
        'municipal_police',
        'civil_protection',
        'command_vehicle',
        'command_troops',
        'command_details',
        'filter_supervisor',
        'name',
        'cp',
        'city',
        'age',
        'amountAlcohol',
        'number_of_passengers',
        'plate_number',
        'detainee_released_to',
        'detainee_phone_number',
        'curp',
        'observations',
        'image_penaltie',
        'created_by',
        'active'
    ];

    public function histories()
    {
        return $this->hasMany(PenaltyHistory::class);
    }
}
