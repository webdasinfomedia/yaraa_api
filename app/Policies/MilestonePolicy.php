<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class MilestonePolicy
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

    public function canEdit(User $user, $projectId)
    {
        $project = Project::find($projectId);

        $role = $project->roles()->where('user_id', $user->id)->first();

        if ($role) {
            return $role->role === Project::CAN_EDIT
                ? Response::allow()
                : Response::deny('You do not have permission for this action.');
        }

        return Response::deny('You do not have permission for this action.');
    }
}
