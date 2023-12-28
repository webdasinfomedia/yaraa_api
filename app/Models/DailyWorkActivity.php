<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class DailyWorkActivity extends Model
{
    protected $fillable = ['resume_date', 'pause_date'];

    protected $dates = ['resume_date', 'pause_date'];

    public $timestamps = false;

}
