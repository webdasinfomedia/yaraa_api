<?php

namespace App\Models;

use App\Models\Task;
use App\Models\Project;
use App\Traits\UserFilterable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Jenssegers\Mongodb\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory,  UserFilterable, SoftDeletes;

    // public const IS_ADMIN = 1; defile static global variable

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'role_id', 'name', 'provider', 'about_me', 'designation', "image", "is_verified", "verify_token", "verified_at", "fav_projects", "archived_projects", "timezone", 'web_fcm_token', 'app_fcm_token', 'image_48x48'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $dates = [
        'verified_at',
        'deleted_at'
    ];

    private $_projectTasks = null;
    private $_nonProjectTasks = null;
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * hash the password before creating new user
     */
    protected static function booted()
    {

        static::creating(function ($user) {
            if ($user->password != null) {
                $user->password = Hash::make($user->password);
            }

            try {
                if (User::count() == 0) {
                    $user->role_id = getRoleBySlug('admin')->id;
                } elseif ($user->role_id == null) {
                    $user->role_id = getRoleBySlug('employee')->id;
                }
            } catch (\Exception $e) {
                $user->role_id = null;
            }
        });

        // static::updating(function ($user) {
        //     if($user->password != null){
        //        $user->password = Hash::make($user->password);
        //     }
        // });
    }

    public function projectTasks($search = null)
    {
        $this->_projectTasks = new Collection(); // empty eloquent collection instead of null to avoid errors
        // $this->_projectTasks = $this->tasks->filter(function ($task){
        $this->_projectTasks = $this->tasks($search)->get()->filter(function ($task) {
            if ($task->project) {
                return $task->project->trashed() ? false : true;
            }
        });

        return $this->_projectTasks;
    }

    public function nonProjectTasks($search = null)
    {
        $this->_nonProjectTasks = new Collection(); // empty eloquent collection instead of null to avoid errors
        // $this->_nonProjectTasks = $this->tasks->whereNull('project_id');
        $this->_nonProjectTasks = $this->tasks($search)->whereNull('project_id')->get();
        return $this->_nonProjectTasks;
    }

    public function userTasks($search = null)
    {
        return $this->nonProjectTasks($search)->merge($this->projectTasks($search));
    }

    public function userPublicTasks()
    {
        return $this->userTasks()->where('visibility', 'public');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tasks($search = null)
    {
        if ($search == null) {
            return $this->belongsToMany(Task::class, NULL, 'assignee', 'task_ids');
        } else {
            return $this->belongsToMany(Task::class, NULL, 'assignee', 'task_ids')->where('name', 'like', "%{$search}%");
        }
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, NULL, 'members', 'project_ids');
    }

    public function favourite_projects()
    {
        return $this->belongsToMany(Project::class, NULL, 'favourite_by', 'favourite_projects');
    }

    public function scopeMembers($query)
    {
        return $query->whereNotNull('email')->whereNotNull('name');
    }

    public function todos()
    {
        return $this->embedsMany(Todo::class, NULL);
    }

    public function hasProject($projectId)
    {
        return $this->projects()->where('_id', $projectId)->exists();
    }

    public function localArchiveProject()
    {
        return $this->belongsToMany(Project::class, null, 'archived_by', 'archived_projects');
    }

    public function subTask()
    {
        return $this->hasMany(SubTask::class, 'assign_to');
    }

    // public function localArchiveTask()
    // {
    //     return $this->belongsToMany(Task::class,null,'archived_by','archived_tasks');
    // }

    public function archives()
    {
        return $this->hasMany(Archive::class);
    }

    public function preferences()
    {
        return $this->embedsOne(Preference::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, NULL, 'member_ids', 'conversation_ids');
    }

    public function isAdmin()
    {
        // if(isset($this->role_id)){
        if ($this->role()->exists()) {
            return $this->role->slug == 'admin' ? true : false;
        }
        return false;
    }

    public function apps()
    {
        return $this->hasOne(UserApp::class);
    }
    // public function myTeams()
    // {
    //     return $this->belongsToMany(Teams::class,NULL,'member_ids','team_ids');
    // }

    public function punchDetails()
    {
        return $this->hasMany(PunchDetails::class);
    }

    public function AsCustomerProjects()
    {
        return $this->belongsToMany(Project::class, NULL, 'customer_ids', 'as_customer_project_ids');
    }
}
