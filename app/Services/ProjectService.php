<?php

namespace App\Services;

use App\Events\SendFcmNotification;
use App\Facades\CreateDPWithLetter;
use App\Jobs\CreateMemberInviteAcknowledgeMail;
use App\Jobs\CreateMemberInviteRegisterMail;
use App\Jobs\UpdateTimelineJob;
use App\Mail\CustomerMail;
use App\Models\Conversation;
use App\Models\FailedActivity;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Timeline;
use App\Models\User;
use App\Scopes\ArchiveScope;
use App\Traits\FcmNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ProjectService
{
    use FcmNotification;

    private $activity_data;
    private $project;

    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }

    public function created($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);
        $project = Project::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['project_id']);

        /*** Create Project chat group ***/
        $this->createChatGroup($project);

        /*** store notification & send FCM notification ***/
        $recipients =  $project->members->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        if (getNotificationSettings('project_created') || getNotificationSettings('project_created') == "true") {
            $notification = Notification::create([
                "title" => "You are added to Project",
                "description" => "{ASSIGNED_BY} have assigned you in a project {PROJECT_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "added_in_project",
                "module_id" => $this->activity_data['project_id'],
                "module" => "Project",
                "tags" => [
                    "ASSIGNED_BY" => $this->activity_data['assigned_by'],
                    "PROJECT_NAME" => $project->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "{$this->activity_data['assigned_by']} have assigned you in a project {$project->name}";
            $this->sendFcmNotification($notification, $recipients);
        }

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_project_created') && $project->customers->isNotEmpty()) {
            foreach ($project->customers as $customer) {
                Mail::to($customer->email)->send(new CustomerMail($customer->name, $project->name, 'project_created',$project->created_at));
            }
        }
    }

    private function createChatGroup($project)
    {
        /** Create Conversation Modal as Group ***/
        $conversation = new Conversation();
        $conversation->type = 'group';
        $conversation->name = $project->name;
        $conversation->last_message_at = null;
        $conversation->project_id = $project->id;
        $conversation->created_by = $project->created_by;

        /** Create Group Logo ***/
        if ($project->image) {
            $conversation->logo = $project->image;
        } else {
            $imageName = 'group/logo/' . getUniqueStamp() . '.png';
            $path = 'public/' . $imageName;
            $img = CreateDPWithLetter::create($project->name);
            Storage::put($path, $img->encode());
            $conversation->logo = $imageName;
        }
        $conversation->save();

        /** Add Group Members ***/
        if ($project->members->isNotEmpty()) {
            foreach ($project->members as $userId) {
                $conversation->members()->attach($userId);
            }
        }
    }

    public function userinvited($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);
        $project = Project::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['project_id']);
        $user = User::find($this->activity_data['receiver_id']);
        $receiverName = $user->name ?? $user->email;
        $author = User::find($this->activity_data['invited_by']);

        //send email
        // $token = hash('sha256', Str::random(60));
        // if(!$user->is_verified){
        //     dispatch(new CreateMemberInviteRegisterMail($user, $sender, 'Project', $project->name, $token));
        // }
        // else{  // elseif(!$user->hasProject($project->id)){
        //     dispatch(new CreateMemberInviteAcknowledgeMail($user, $sender, 'Project', $project->name));
        // }


        //store notification
        if (getNotificationSettings('project_invite_member') || getNotificationSettings('project_invite_member') == "true") {
            $notification = Notification::create([
                "title" => "User Invited to Project",
                "description" => "{INVITED_BY} have assigned you in a project {PROJECT_NAME}",
                "receiver_ids" => [$this->activity_data['receiver_id']],
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "added_in_project",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "INVITED_BY" => $author->name,
                    "PROJECT_NAME" => $project->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "{$author->name} has added you in a project {$project->name}";
            $recipients = [$this->activity_data['receiver_id']];
            $this->sendFcmNotification($notification, $recipients);
        }

        /** Project leader notification **/
        $sender = $project->owner->id == $author->id ? "You added" : "{INVITED_BY} has added";
        $notification = Notification::create([
            "title" => "User Invited to Project",
            "description" => "{$sender} {RECEIVER_NAME} in a project {PROJECT_NAME}",
            "receiver_ids" => [$project->created_by],
            "activity_id" => $activity['activity_id'],
            "ready_by" => [],
            "type" => "added_in_project",
            "module_id" => $project->id,
            "module" => "Project",
            "tags" => [
                "INVITED_BY" => $author->name,
                "RECEIVER_NAME" => $receiverName,
                "PROJECT_NAME" => $project->name,
            ],
        ]);

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $sender = $project->owner->id == $author->id ? "You added" : "{$author->name} has added";
            $notification->description = "{$sender} {$receiverName} in a project {$project->name}";
            $recipients = [$project->owner->id];
            $this->sendFcmNotification($notification, $recipients);
        }
    }

    public function completed($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);
        $project = Project::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['project_id']);
        $author = User::find($activity['activity_by']);
        $recipients = $project->members->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('project_completed') || getNotificationSettings('project_completed') == "true") {
            $notification = Notification::create([
                "title" => "Project Completed",
                "description" => "Project {PROJECT_NAME} marked as completed by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "project_completed",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $author->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "{$project->name} marked as completed by {$author->name}";
            $this->sendFcmNotification($notification, $recipients);
        }

        $timelineFeed = [
            "module" => "project",
            "status" => "created",
            "module_id" => $project->id,
            "project_id" => $project->id,
            "created_by" => $activity['activity_by'],
            "description" => "Project Completed : {$project->name}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);

         /** Send Mail notification to Customer */
         if (getNotificationSettings('email') && getNotificationSettings('customer_project_completed') && $project->customers->isNotEmpty()) {
            foreach ($project->customers as $customer) {
                Mail::to($customer->email)->send(new CustomerMail($customer->name, $project->name, 'project_completed',$project->created_at));
            }
        }
    }

    public function reopened($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);
        // $project = Project::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['project_id']);
        $project = Project::withTrashed()->withArchived()->find($this->activity_data['project_id']);
        $author = User::find($activity['activity_by']);
        $recipients = $project->members->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('project_reopen') || getNotificationSettings('project_reopen') == "true") {
            $notification = Notification::create([
                "title" => "Project Reopened",
                "description" => "Project {PROJECT_NAME} re-opened by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "project_reopen",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $author->name,
                ],
            ]);
        }


        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "{$project->name} re-opened by {$author->name}";
            $this->sendFcmNotification($notification, $recipients);
        }

        $timelineFeed = [
            "module" => "project",
            "status" => "re-opened",
            "module_id" => $project->id,
            "project_id" => $project->id,
            "created_by" => $activity['activity_by'],
            "description" => "Project Re-opened : {$project->name}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_project_reopen') && $project->customers->isNotEmpty()) {
            foreach ($project->customers as $customer) {
                Mail::to($customer->email)->send(new CustomerMail($customer->name, $project->name, 'project_reopen',$project->created_at));
            }
        }
    }

    public function deleted($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        // $project = Project::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['project_id']);
        $project = Project::withTrashed()->withArchived()->find($this->activity_data['project_id']);
        $recipients = $project->members->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('project_deleted') || getNotificationSettings('project_deleted') == "true") {
            $notification = Notification::create([
                "title" => "Project Deleted",
                "description" => "Project {PROJECT_NAME} permanently deleted by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "project_deleted",
                "module_id" => null,
                "module" => "Project",
                "tags" => [
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Project {$activity['project_name']} permanently deleted by {$activity['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }
    }

    public function restore($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        // $project = Project::withoutGlobalScope(ArchiveScope::class)->find($activity_data['project_id']);
        $project = Project::withTrashed()->withArchived()->find($activity_data['project_id']);
        $recipients = $project->members->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('project_deleted') || getNotificationSettings('project_deleted') == "true") {
            $notification = Notification::create([
                "title" => "Project Restored",
                "description" => "Project {PROJECT_NAME} restored by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "project_restore",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Project {$project->name} restored by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }
    }
}
