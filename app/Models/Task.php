<?php

namespace App\Models;

use App\Traits\MarkItem;
use App\Traits\TaskActivity;
use App\Traits\TaskFilterable;
use App\Traits\MongoArchivable;
use App\Traits\SyncUsers;
use App\Traits\TaskActivityLog;
use App\Traits\TaskDeletable;
use App\Traits\TaskProgressible;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class Task extends Model
{
    use TaskFilterable, TaskProgressible, SoftDeletes, MarkItem, MongoArchivable, TaskActivity, TaskActivityLog, TaskDeletable, SyncUsers;

    protected $fillable = ['name', 'assignee', 'assigned_by', 'description', 'visibility', 'priority', 'attachments', 'project_id', 'start_date', 'end_date', 'due_date', 'pause_date', 'resume_date', 'status', 'activity_logs', 'task_comment_ids', 'milestone_ids', 'is_deleting', 'reminder', 'recurrence', 'task_id', 'created_by', 'due_24h_mail_send_to'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $dates = [
        'start_date',
        'end_date',
        'pause_date',
        'resume_date',
        'due_date',
    ];

    public function assignedTo()
    {
        return $this->belongsToMany(User::class, null, 'task_ids', 'assignee');
    }

    protected static function booted()
    {
        if (!app()->runningInConsole()) {
            static::creating(function ($task) {
                $task->created_by = auth()->user()->id;
                $task->assigned_by = auth()->user()->id;
                $task->archived_by = [];
                $task->due_24h_mail_send_to = [];
                // $task->due_date = Carbon::parse($task->due_date)->timestamp($task->due_date);
                // $task->due_date = $task->due_date->toDateTime();
                // $task->due_date = \MongoDB\BSON\UTCDateTime(new DateTime($tas->toDateTimeString()));
            });
        }
    }

    public function createdBy($userId)
    {
        return $this->created_by == $userId ? true : false;
    }

    public function getStartDateAttribute($value)
    {
        if (!is_null($value)) {
            $date = is_string($value) ? Carbon::parse($value) : $value;
            $date = Carbon::create($date->toDateTime());
            $date->setTimezone(getUserTimezone());
            return Carbon::create($date->toDateTimeString());
        } else {
            return $value;
        }
    }

    public function getDueDateAttribute($value)
    {
        if (!is_null($value)) {
            $date = is_string($value) ? Carbon::parse($value) : $value;
            $date = Carbon::create($date->toDateTime());
            $date->setTimezone(getUserTimezone());
            return Carbon::create($date->toDateTimeString());
        } else {
            return $value;
        }
    }

    public function milestones()
    {
        // return $this->embedsMany(Milestone::class);
        return $this->belongsToMany(Milestone::class, NULL, 'task_ids', 'milestone_ids');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, null, 'task_ids', 'tags');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function subTasks()
    {
        return $this->hasMany(SubTask::class);
    }

    /**
     * Show which has archived project personally
     */
    // public function archivedBy()
    // {
    //     return $this->belongsToMany(User::class,null,'archived_tasks','archived_by');
    // }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function details()
    {
        return $this->hasMany(TaskDetail::class);
    }

    public function myDetails()
    {
        return $this->hasOne(TaskDetail::class)->where('user_id', auth()->id());
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(getDefaultUserModel());
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
