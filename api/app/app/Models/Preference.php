<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Preference extends Model
{
    protected $hidden = [
        '_id',
        'created_at',
        'updated_at',
    ];

    protected $guarded = [];
}
