<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Notification;
use App\Models\FailedActivity;
use App\Models\Task;
use App\Traits\FcmNotification;

class ZoomService
{
    use FcmNotification;

    private $activity_data;

    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }

    public function meeetingcreated($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $notification_data = json_decode($activity['notification_data'], true);

        if($activity_data['module'] == 'task'){
            $task = Task::withoutGlobalScope(ArchiveScope::class)->find($activity_data['module_id']);
            $recipients = $task->assignedTo->pluck('id')->toArray();
        }
        elseif($activity_data['module'] == 'message'){
            $conversation = Conversation::find($activity_data['module_id']);
            $recipients = $conversation->members->pluck('id')->toArray();
        }else{
            exit;
        }

        $key = array_search($activity_data['auth_id'], $recipients);
        unset($recipients[$key]);
        $recipients = array_values($recipients); //re-arrange keys to avoid storing as object

        //store notification
        if (getNotificationSettings('message_notify_recipients') || getNotificationSettings('message_notify_recipients') == "true") {
            $notification = Notification::create([
                "title" => $activity['activity_title'],
                "description" => $activity['activity_title'] . " by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "zoom_meeting_created",
                "module_id" => $activity_data['module_id'],
                "module" => $activity_data['module'],
                "tags" => [
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->title = $notification_data['title'];
            $notification->description = $notification_data['description'];
            $this->sendFcmNotification($notification, $recipients);
        }
    }

}
