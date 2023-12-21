<?php

namespace App\Models;

use App\Traits\Timezone;
use Jenssegers\Mongodb\Eloquent\Model;

class Notification extends Model
{
    use Timezone;

    protected $fillable = [
        'title',
        'description',
        'receiver_ids',
        'activity_id',
        'read_by',
        'type',
        'module_id',
        'module',
        'tags'
    ];

    protected static function booted()
    {
        static::creating(function ($notification) {
            $notification->is_read = [];
        });
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
