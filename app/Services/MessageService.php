<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Notification;
use App\Models\FailedActivity;
use App\Traits\FcmNotification;

class MessageService
{
    use FcmNotification;

    private $activity_data;

    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }

    public function received($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $notification_data = json_decode($activity['notification_data'], true);
        $conversation = Conversation::find($activity_data['conversation_id']);
        $recipients = $conversation->members->pluck('id')->toArray();
        $key = array_search($activity_data['auth_id'], $recipients);
        unset($recipients[$key]);
        $recipients = array_values($recipients); //re-arrange keys to avoid storing as object

        if ($conversation->type == 'personal') {
            // $description = "New message from {AUTHOR_NAME}";
            $description = $notification_data['body'];
            $title = "New message from {AUTHOR_NAME}";
            $pushTitle = "New message from {$activity_data['author_name']}";
            $tags = ["AUTHOR_NAME" => $activity_data['author_name']];
        } else {
            $description = $notification_data['body'];
            $title = "New message from {AUTHOR_NAME} ({GROUP_NAME})";
            $pushTitle = "New message from {$activity_data['author_name']} ({$conversation->name})";
            $tags = ["AUTHOR_NAME" => $activity_data['author_name'], "GROUP_NAME" => trim($conversation->name)];
        }
        //store notification
        if (getNotificationSettings('message_notify_recipients') || getNotificationSettings('message_notify_recipients') == "true") {
            $notification = Notification::create([
                "title" => $title,
                "description" => $title,
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "message_received",
                "module_id" => $conversation->id,
                "module" => "Conversation",
                "tags" => $tags,
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->title = $pushTitle;
            $notification->description = $notification_data['body'];
            $this->sendFcmNotification($notification, $recipients);
        }
    }
}
