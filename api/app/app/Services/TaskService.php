<?php

namespace App\Services;

use App\Jobs\UpdateTimelineJob;
use App\Mail\CustomerMail;
use App\Models\Notification;
use App\Models\FailedActivity;
use App\Models\Task;
use App\Models\Timeline;
use App\Models\User;
use App\Traits\FcmNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class TaskService
{
    use FcmNotification;

    private $activity_data;

    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }


    public function created($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['task_id']);
        $recipients = $task->assignedTo->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        if (getNotificationSettings('task_created') || getNotificationSettings('task_created') == "true") {
            $notification = Notification::create([
                "title" => "Task Created",
                "description" => "{AUTHOR_NAME} has assigned you to Task {TASK_TITLE}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_created",
                "module" => 'Task',
                "module_id" => $task->id,
                "tags" => [
                    "AUTHOR_NAME" => $activity_data['author_name'],
                    "TASK_TITLE" => $task->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "{$activity_data['author_name']} has assigned you to Task {$task->name}";
            $this->sendFcmNotification($notification, $recipients);
        }

        if ($task->project_id != null) {

            $timelineFeed = [
                "module" => "task",
                "status" => "created",
                "module_id" => $task->id,
                "project_id" => $task->project_id,
                "created_by" => $activity['activity_by'],
                "description" => "Task Created : {$task->name}",
                "activity_at" => Carbon::now(),
            ];

            Timeline::create($timelineFeed);
        }

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_task_created') && $task->project()->exists()) {
            if ($task->project->customers->isNotEmpty()) {
                foreach ($task->project->customers as $customer) {
                    Mail::to($customer->email)->send(new CustomerMail($customer->name, $task->name, 'task_created', $task->created_at));
                }
            }
        }
    }

    public function completed($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['task_id']);
        $recipients = $task->assignedTo->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('task_completed') || getNotificationSettings('task_completed') == "true") {
            $notification = Notification::create([
                "title" => "Task Completed",
                "description" => "Task {TASK_NAME} marked as completed by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_completed",
                "module_id" => $task->id,
                "module" => "Task",
                "tags" => [
                    "TASK_NAME" => $task->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Task {$task->name} marked as completed by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }

        if ($task->project_id != null) {

            $timelineFeed = [
                "module" => "task",
                "status" => "completed",
                "module_id" => $task->id,
                "project_id" => $task->project_id,
                "created_by" => $activity['activity_by'],
                "description" => "Task Completed : {$task->name}",
                "activity_at" => Carbon::now(),
            ];

            Timeline::create($timelineFeed);
        }

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_task_completed') && $task->project->customers->isNotEmpty()) {
            foreach ($task->project->customers as $customer) {
                Mail::to($customer->email)->send(new CustomerMail($customer->name, $task->name, 'task_completed', $task->created_at));
            }
        }
    }

    public function reopened($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['task_id']);
        $recipients = $task->assignedTo->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('task_uncompleted') || getNotificationSettings('task_uncompleted') == "true") {
            $notification = Notification::create([
                "title" => "Task Reopened",
                "description" => "Task {TASK_NAME} is re-opened by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_reopened",
                "module_id" => $task->id,
                "module" => "Task",
                "tags" => [
                    "TASK_NAME" => $task->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Task {$task->name} is re-opened by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }

        if ($task->project_id != null) {
            $timelineFeed = [
                "module" => "task",
                "status" => "re-opened",
                "module_id" => $task->id,
                "project_id" => $task->project_id,
                "created_by" => $activity['activity_by'],
                "description" => "Task Re-opened : {$task->name}",
                "activity_at" => Carbon::now(),
            ];

            Timeline::create($timelineFeed);
        }

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_task_reopen') && $task->project->customers->isNotEmpty()) {
            foreach ($task->project->customers as $customer) {
                Mail::to($customer->email)->send(new CustomerMail($customer->name, $task->name, 'task_reopen', $task->created_at));
            }
        }
    }

    public function deleted($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);

        //store notification
        if (getNotificationSettings('task_deleted') || getNotificationSettings('task_deleted') == "true") {
            $notification = Notification::create([
                "title" => "Task Deleted",
                "description" => "Task {TASK_NAME} deleted by {AUTHOR_NAME}",
                "receiver_ids" => $activity_data['recipients'],
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_deleted",
                "module_id" => $activity_data['task_id'],
                "module" => "Task",
                "tags" => [
                    "TASK_NAME" => $activity_data['task_name'],
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Task {$activity_data['task_name']} deleted by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $activity_data['recipients']);
        }
    }

    public function commentadded($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['task_id']);
        $recipients = $task->assignedTo->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('task_comment') || getNotificationSettings('task_comment') == "true") {
            $notification = Notification::create([
                "title" => "New Message in Task",
                "description" => "You have a new unread message in task {TASK_NAME} from {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_new_message",
                "module_id" => $task->id,
                "module" => "Task",
                "tags" => [
                    "TASK_NAME" => $task->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "You have a new unread message in task {$task->name} from {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }
    }

    public function restore($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['task_id']);
        $recipients = $task->assignedTo->pluck('id')->toArray();
        foreach (array_keys($recipients, $activity['activity_by'], true) as $key) {
            unset($recipients[$key]);
        }
        $recipients = array_values($recipients);

        //store notification
        if (getNotificationSettings('task_restored') || getNotificationSettings('task_restored') == "true") {
            $notification = Notification::create([
                "title" => "Task Restored",
                "description" => "Task {TASK_NAME} restored by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "task_restore",
                "module_id" => $task->id,
                "module" => "Task",
                "tags" => [
                    "TASK_NAME" => $task->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Task {$task->name} restored by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }
    }

    public function userinvited($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);
        $task = Task::withoutGlobalScope(ArchiveScope::class)->find($this->activity_data['task_id']);
        // $user = User::find($this->activity_data['receiver_id']);
        // $receiverName = $user->name ?? $user->email;
        // $author = User::find($this->activity_data['invited_by']);
        $author = User::find($activity['activity_by']);

        //store notification
        if (getNotificationSettings('task_invite_member') || getNotificationSettings('task_invite_member') == "true") {
            $notification = Notification::create([
                "title" => "Member Invited to Task",
                "description" => "{INVITED_BY} has added you to task {TASK_NAME}",
                "receiver_ids" => [$this->activity_data['receiver_id'], $task->created_by],
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "added_in_task",
                "module_id" => $task->id,
                "module" => "Task",
                "tags" => [
                    "INVITED_BY" => $author->name,
                    "TASK_NAME" => $task->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            // $notification->description = "{$this->activity_data['invited_by']} has assigned you to task {$task->name}";
            $notification->description = "{$author->name} has assigned you to task {$task->name}";
            $recipients = [$this->activity_data['receiver_id']];
            $this->sendFcmNotification($notification, $recipients);
        }
    }
}
