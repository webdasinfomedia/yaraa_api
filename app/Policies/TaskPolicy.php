<?php

namespace App\Policies;

// use App\User;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function store(User $user, $projectId)
    {
        $project = Project::find($projectId);

        $role = $project->roles()->where('user_id', $user->id)->first();

        if ($role) {
            return $role->role === Project::CAN_EDIT
                ? Response::allow()
                : Response::deny('You do not have permission to create task');
        }

        return Response::deny('You do not have permission to create task');
    }

    // public function update(User $user, $projectId)
    // {
    //     $project = Project::find($projectId);

    //     $role = $project->roles()->where('user_id', $user->id)->first();

    //     if ($role) {
    //         return $role->role === Project::CAN_EDIT
    //             ? Response::allow()
    //             : Response::deny('You do not have permission to update task');
    //     }

    //     return Response::deny('You do not have permission to update task');
    // }

    // public function delete(User $user, $projectId)
    // {
    //     $project = Project::find($projectId);

    //     $role = $project->roles()->where('user_id', $user->id)->first();

    //     if ($role) {
    //         return $role->role === Project::CAN_EDIT
    //             ? Response::allow()
    //             : Response::deny('You do not have permission to delete task');
    //     }

    //     return Response::deny('You do not have permission to delete task');
    // }

    // public function canEdit(User $user, $projectId)
    // {
    //     $project = Project::find($projectId);

    //     $role = $project->roles()->where('user_id', $user->id)->first();

    //     if ($role) {
    //         return $role->role === Project::CAN_EDIT
    //             ? Response::allow()
    //             : Response::deny('You do not have permission for this action');
    //     }

    //     return Response::deny('You do not have permission for this action');
    // }
    public function canEdit(User $user, Task $task)
    {
        if ($task->project()->exists()) {

            $role = $task->project->roles()->where('user_id', $user->id)->first();

            if ($role) {
                return $role->role === Project::CAN_EDIT
                    ? Response::allow()
                    : Response::deny('You do not have permission for this action');
            }
        } else {
            return $task->assignedTo->contains($user->id)
                ? Response::allow()
                : Response::deny('You do not have permission for this action');
        }

        return Response::deny('You do not have permission for this action');
    }
}
