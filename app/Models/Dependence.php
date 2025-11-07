<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependence extends Model
{
      protected $primaryKey = 'id';
    protected $table = 'dependence';
    protected $fillable = ['active', 'name'.'color'];

}
