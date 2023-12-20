<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskCommentPolicy
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

    public function addComment(User $user, Task $task)
    {
        if ($task->project()->exists()) {

            $role = $task->project->roles()->where('user_id', $user->id)->first();

            if ($role) {
                return $role->role === Project::CAN_EDIT || $role->role === Project::CAN_COMMENT
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

    // public function trackLocation(User $user, Task $task)
    // {
    //     $role = $task->project->roles()->where('user_id', $user->id)->first();

    //     if ($role) {
    //         return $role->role === Project::CAN_EDIT || $role->role === Project::CAN_COMMENT
    //             ? Response::allow()
    //             : Response::deny('You do not have permission to track location');
    //     }

    //     return Response::deny('You do not have permission to track location');
    // }

    // public function createZoomMeeting(User $user, Task $task)
    // {
    //     $role = $task->project->roles()->where('user_id', $user->id)->first();

    //     if ($role) {
    //         return $role->role === Project::CAN_EDIT || $role->role === Project::CAN_COMMENT
    //             ? Response::allow()
    //             : Response::deny('You do not have permission to create zoom meeting.');
    //     }

    //     return Response::deny('You do not have permission to track location');
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
