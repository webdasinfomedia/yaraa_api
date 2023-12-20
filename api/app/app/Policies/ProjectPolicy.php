<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;


class ProjectPolicy
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

    public function index()
    {
        return true;
    }

    public function update(User $user, Project $project)
    {
        $role = $project->roles()->where('user_id', $user->id)->first();

        if ($role) {
            return $role->role === Project::CAN_EDIT
                ? Response::allow()
                : Response::deny('You do not have permission to update project');
        }

        return Response::deny('You do not have permission to update project');
    }
}
