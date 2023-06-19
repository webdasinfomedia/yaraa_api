<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'activity',
        'activity_title',
        'activity_by',
        'activity_time',
        'activity_data',
    ];

    protected $dates = [
        'activity_time'
    ];

    public function author()
    {
        return $this->belongsTo(User::class,'activity_by');
    }
}
