<?php

namespace App\Models;

use App\Traits\MarkItem;
use Jenssegers\Mongodb\Eloquent\Model;

class Todo extends Model
{
    use MarkItem;

    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'due_date', 'priority', 'attachments', 'status', 'pause_date', 'resume_date', 'visibility', 'reminder', 'reminder_sent', 'recurrence', 'todo_id'];

    protected $dates = [
        'start_date',
        'end_date',
        'pause_date',
        'resume_date',
        'due_date',
    ];

    public function todos()
    {
        return $this->hasMany(Todo::class, 'todo_id');
    }

    public function parentTodo()
    {
        return $this->belongsTo(Todo::class, 'todo_id');
    }
}
