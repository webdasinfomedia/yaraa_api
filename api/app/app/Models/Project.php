<?php

namespace App\Models;

use Auth;
use App\Models\User;
use App\Traits\ManageProjectRole;
use App\Traits\MarkItem;
use App\Traits\MongoArchivable;
use App\Traits\ProjectDeletable;
use App\Traits\TaskFilterable;
use App\Traits\ProjectProgressible;
use App\Traits\SyncUsers;
use App\Traits\Timezone;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Project extends Eloquent
{
    //soft delete used as archive
    use SoftDeletes, TaskFilterable, ProjectProgressible, MongoArchivable, MarkItem, ProjectDeletable, SyncUsers, Timezone, ManageProjectRole;

    protected $fillable = ['name', 'privacy', 'created_by', 'board_view', 'visibility', 'image', 'description', 'members', 'task_ids', 'attachments', 'milestones', 'start_date', 'due_date', 'status', 'activity_logs', 'tags', 'end_date', 'is_deleting'];

    protected $dates = [
        'start_date',
        'due_date',
        'end_date',
        'created_at',
    ];

    /** roles for the project model */
    public const CAN_EDIT = 'can_edit';
    public const CAN_COMMENT = 'can_comment';
    public const CAN_VIEW = 'can_view';

    /**
     * add created by field when creating new project
     */
    protected static function booted()
    {
        // static::addGlobalScope('all_project', function (Builder $builder) {
        //     $builder->where('status','completed');
        // });

        static::creating(function ($project) {
            $project->created_by = Auth::user()->id;
            $project->archived_by = [];
        });

        // static::deleting(function (Film $film) {
        //     $attributes = $film->getAttributes();
        //     Storage::delete($attributes['background_cover']);
        //     Storage::delete($attributes['poster']);
        // });
    }

    // public function getPosterAttribute($value)
    // {
    //     return asset('storage/' . $value);
    // }


    public function createdBy($userId)
    {
        return $this->created_by == $userId ? true : false;
    }

    public function currentStatus()
    {
        $status = $this->status;
        $now = Carbon::now();
        if ($this->due_date != null && $this->end_date == null) {
            $status = $this->due_date->lessThan($now) ? 'delayed' : $this->status;
        }

        return $status;
    }

    public function members()
    {
        return $this->belongsToMany(User::class, NULL, 'project_ids', 'members');
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, null, 'project_ids', 'tags');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function added_favourite_by()
    {
        return $this->belongsToMany(User::class, NULL, 'favourite_projects', 'favourite_by');
    }

    /**
     * Show which has archived project personally
     */
    // public function archivedBy()
    // {
    //     return $this->belongsToMany(User::class,null,'archived_projects','archived_by');
    // }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(getDefaultUserModel());
    }

    public function customers()
    {
        // return $this->belongsToMany(Customer::class, NULL, 'project_ids', 'customer_ids');
        return $this->belongsToMany(User::class, NULL, 'as_customer_project_ids', 'customer_ids');
        // return $this->belongsTo(User::class, 'customer_id');
    }

    public function roles()
    {
        return $this->hasMany(ProjectRole::class);
    }
}
