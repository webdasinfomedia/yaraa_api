<?php

namespace App\Models;

use App\Traits\MarkItem;
use Jenssegers\Mongodb\Eloquent\Model;


class TaskComment extends Model
{
    use MarkItem;

    protected $fillable = [
        'task_id',
        'user_id',
        'message',
        'type',
        'attachments',
        'read_by',
        'zoom_details',
        'details',
        'meet_details',
        'location_details',
        'marked_important_by',
    ];

    protected static function booted()
    {
        static::creating(function ($taskComment) {
            $taskComment->read_by = []; //create & save empty array
            $taskComment->created_by = auth()->id();
            $taskComment->marked_important_by = [];
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(getDefaultUserModel());
    }
}
