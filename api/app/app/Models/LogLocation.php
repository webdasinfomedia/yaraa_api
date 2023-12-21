<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class LogLocation extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'email'
    ];
}
