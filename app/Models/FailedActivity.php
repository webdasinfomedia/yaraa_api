<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class FailedActivity extends Model
{
    protected $fillable = [
        'error_data',
        'activity_data',
    ];
}
