<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Characteristic extends Model
{
    protected $fillable = [
        'characteristic_architecture_id',
        'characteristic_unit_id',
        'capacity',
    ];
}
