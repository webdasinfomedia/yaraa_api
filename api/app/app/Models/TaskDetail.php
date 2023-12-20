<?php

namespace App\Models;

use App\Traits\MarkItem;
use Jenssegers\Mongodb\Eloquent\Model;

class TaskDetail extends Model
{
    use MarkItem;
    
    protected $fillable = [
        'task_id',
        'user_id',
        'status',
        'start_date',
        'pause_date',
        'end_date',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'pause_date'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function dailyActivity()
    {
        return $this->embedsMany(DailyWorkActivity::class);
    }
}
