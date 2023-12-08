<?php

namespace App\Models;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class PunchDetail extends Model
{
    protected $fillable = [
        "user_id",
        "punch_in",
        "punch_out",
        "breaks",
        "total_work_hour",
    ];

    protected $dates = ['punch_in', 'punch_out'];

    public function getPunchInUsertimezoneAttribute()
    {
        if (!is_null($this->punch_in)) {
            $date = is_string($this->punch_in) ? Carbon::parse($this->punch_in) : $this->punch_in;
            $date = Carbon::create($date->toDateTime());
            $date->setTimezone(getUserTimezone());
            return Carbon::create($date->toDateTimeString());
        } else {
            return $this->punch_in;
        }
    }

    public function getPunchOutUsertimezoneAttribute()
    {
        if (!is_null($this->punch_out)) {
            $date = is_string($this->punch_out) ? Carbon::parse($this->punch_out) : $this->punch_out;
            $date = Carbon::create($date->toDateTime());
            $date->setTimezone(getUserTimezone());
            return Carbon::create($date->toDateTimeString());
        } else {
            return $this->punch_out;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
