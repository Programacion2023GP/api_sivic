<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sender extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'senders';
    protected $fillable = ['active', 'name'];
}
