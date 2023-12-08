<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeZone;

trait Timezone
{

    public function getCreatedAtAttribute($value)
    {
        if (!is_string($value)) {
            $date = Carbon::create($value->toDateTime());
            $date->setTimezone(getUserTimezone());
            return Carbon::create($date->toDateTimeString());
        } else {
            return $value;
        }
    }

    public function getUpdatedAtAttribute($value)
    {
        if (!is_string($value)) {
            $date = Carbon::create($value->toDateTime());
            $date->setTimezone(getUserTimezone());
            // return $date->setTimezone(getUserTimezone())->format('Y-m-d\TH:i:s.u');
            return Carbon::create($date->toDateTimeString());
        } else {
            return $value;
        }
    }

    public function getCreatedAtOriginalAttribute()
    {
        return $this->attributes['created_at'];
    }
}
