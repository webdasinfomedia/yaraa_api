<?php

namespace App\Models;

use App\Traits\MarkItem;
use Jenssegers\Mongodb\Eloquent\Model;

class SubTask extends Model
{
    use MarkItem;
    
    protected $fillable = [
        'name',
        'due_date',
        'assignee',
        'end_date',
        'task_id',
        'status',
    ];

    // public $assignee;
    
    protected $dates = [
        'due_date',
        'end_date'
    ];

    public function parentTask()
    {
        return $this->belongsTo(Task::class,'task_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class,'assign_to');
    }
}
