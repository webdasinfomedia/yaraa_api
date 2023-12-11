<?php

namespace App\Listeners;

use App\Events\UserDeleteEvent;
use App\Models\Conversation;
use App\Models\LogLocation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PasswordReset;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDetail;
use App\Models\TenantSlaveUser;
use App\Models\UserApp;
use Illuminate\Support\Arr;

class UserDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserDeleteEvent  $event
     * @return void
     */
    public function handle(UserDeleteEvent $event)
    {
        $user = $event->user;

        //Remove notifications
        $this->deleteNotifications($user);

        //Remove Password reset Tokens
        PasswordReset::whereEmail($user->email)->delete();

        //Remove Log Locations
        $this->removeLogLocation($user);

        //Remove Timelines
        //skip for now

        //Remove User apps & deauthorize access tokens
        $this->removeApps($user);

        //Remove from ready_by in models
        $this->removeFromReadyBy($user);

        //Remove from message groups & remove group if no members
        $this->removeFromMessageGroup($user);

        //Remove from deleted_by
        $this->removeFromDeletedBy($user);

        //Remove from deleted_by
        $this->removeFromArchivedBy($user);

        //Remove from subtasks
        $this->removeSubTasks($user);

        //Remove from tasks Details Table
        $this->removeTaskDetails($user);

        //Remove from tasks
        $this->removeFromTask($user);

        //Remove from projects
        $this->removeFromProject($user);

        //Remove from projects role
        $this->removeFromProjectRole($user);

        //Remove from Tenant Slave user Table
        $this->removeFromTenantSlaveUser($user);

        //Remove user permanently
        $user->forceDelete();
    }

    public function deleteNotifications($user)
    {
        Notification::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'receiver_ids' => $user->id,
                    ]
                ]
            );
        });
    }

    public function removeLogLocation($user)
    {

        LogLocation::whereEmail($user->email)->delete();

        $taskComments = TaskComment::where('type', 'location')->where('created_by', $user->id)->get();

        $taskComments->each(function ($comment) use ($user) {
            $locations = json_decode($comment->details, true);
            if (!empty($locations)) {
                $filtered = Arr::where($locations, function ($locations) use ($user) {
                    return $locations['email'] != $user->email;
                });

                $comment->details = json_encode(array_values($filtered));
                $comment->save();
            }
        });
    }

    public function removeApps($user)
    {
        deauthorizeZoomAccessToken(auth()->id());

        $user->apps()->whereIn('type', ['apps', 'zoom_token'])->delete();
    }

    public function removeFromReadyBy($user)
    {
        Message::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'read_by' => $user->id,
                    ]
                ]
            );
        });
    }

    public function removeFromMessageGroup($user)
    {
        $projects = $user->projects;
        $projects->each(function ($project) use ($user) {
            $group = $project->conversation;
            if ($group) {
                if ($group->created_by == $user->id) {
                    $members = $group->members->pluck('id')->toArray();
                    $key = array_search($user->id, $members);
                    unset($members[$key]);
                    $members = array_values($members);
                    $group->created_by = $members[0];
                    $group->save();
                }
                $group->members()->detach($user->id);
            }
        });

        $user->conversations->each(function ($conversation) use ($user) {
            if ($conversation->created_by == $user->id) {
                $members = $conversation->members->pluck('id')->toArray();
                $key = array_search($user->id, $members);
                unset($members[$key]);
                $members = array_values($members);
                $conversation->created_by = $members[0];
                $conversation->save();
            }
            $user->conversations()->detach($conversation->id);
        });
    }

    public function removeFromDeletedBy($user)
    {
        /** Remove from message model **/
        Message::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'deleted_by' => $user->id,
                    ]
                ]
            );
        });

        /** Remove from conversation model **/
        Conversation::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'deleted_by' => $user->id,
                    ]
                ]
            );
        });
    }

    public function removeFromArchivedBy($user)
    {
        /** Remove from message model **/
        Project::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'archived_by' => $user->id,
                    ]
                ]
            );
        });

        /** Remove from conversation model **/
        Task::raw(function ($collection) use ($user) {
            return $collection->updateMany(
                [],
                [
                    '$pull' => [
                        'archived_by' => $user->id,
                    ]
                ]
            );
        });
    }

    public function removeSubTasks($user)
    {
        SubTask::where('assign_to', $user->id)->delete();
    }

    public function removeTaskDetails($user)
    {
        TaskDetail::where('user_id', $user->id)->delete();
    }

    public function removeFromTask($user)
    {
        $user->tasks->each(function ($task) use ($user) {

            $members = $task->assignedTo->pluck('id')->toArray();

            if ($task->created_by == $user->id  && sizeof($members) > 1) {
                $key = array_search($user->id, $members);
                unset($members[$key]);
                $members = array_values($members);
                $task->created_by = $members[0];
                $task->save();
            }

            $task->assignedTo()->detach($user);

            if ($task->assignedTo->isEmpty()) {
                $task->processDelete();
            }
        });
    }

    public function removeFromProject($user)
    {
        $user->projects->each(function ($project) use ($user) {

            if ($project->created_by == $user->id) {
                $members = $project->members->pluck('id')->toArray();
                $key = array_search($user->id, $members);
                unset($members[$key]);
                $members = array_values($members);
                $project->created_by = $members[0];
                $project->save();

                /** Make sure new owner has can edit role */
                ProjectRole::updateOrCreate(
                    ["project_id" => $project->id, "user_id" => $members[0]],
                    ["role" => Project::CAN_EDIT]
                );
            }

            $project->members()->detach($user);

            if ($project->members->isEmpty()) {
                $project->processDelete();
            }
        });

        $user->projects()->withTrashed()->each(function ($project) {
            $project->forceDelete();
        });
    }

    public function removeFromProjectRole($user)
    {
        ProjectRole::where('user_id', $user->id)->delete();
    }

    public function removeFromTenantSlaveUser($user)
    {
        //Check if user is part of multiple tenant
        $slaveUser = TenantSlaveUser::whereEmail($user->email)->first();
        $hasMultiOrganization = sizeof($slaveUser->tenant_ids) > 1 ? true : false;

        if ($hasMultiOrganization) {

            /** Remove current tenant from list & disabled list **/
            TenantSlaveUser::raw(function ($collection) use ($user) {
                return $collection->updateMany(
                    ['email' => $user->email],
                    [
                        '$pull' => [
                            'tenant_ids' => app('tenant')->id,
                            'disabled_tenant_ids' => app('tenant')->id,
                        ]
                    ]
                );
            });

            /** Change default tenant **/
            $tenants = $slaveUser->tenant_ids;
            $key = array_search(app('tenant')->id, $tenants);
            unset($tenants[$key]);
            $tenants = array_values($tenants);
            $slaveUser->default_tenant = $tenants[0];
            $slaveUser->save();
        } else {
            //Permanent delete the tenant slave user
            $slaveUser->forceDelete();
        }
    }
}
