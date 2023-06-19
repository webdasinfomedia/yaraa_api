<?php

namespace App\Models;

use App\Traits\Timezone;
use Jenssegers\Mongodb\Eloquent\Model;

class Message extends Model
{
    use Timezone;

    protected $fillable = [
        'conversation_id',
        'body',
        'attachments',
        'created_by',
        'read_by',
        'deleted_by',
        'type',
        'zoom_details',
        'meet_details',
    ];

    protected static function booted()
    {
        static::creating(function ($message) {
            $message->created_by = auth()->id();
        });
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('deleted_by', [auth()->id()]);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(getDefaultUserModel());
    }

    public function clean()
    {
        $this->push('deleted_by', auth()->id());
    }
}
