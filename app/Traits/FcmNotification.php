<?php

namespace App\Traits;

use App\Events\SendFcmNotification;
use App\Models\Notification;
use App\Models\User;

trait FcmNotification
{
    public function sendFcmNotification(Notification $notification, $recipients)
    {
        $tokens = [];

        foreach ($recipients as $recipient) {
            $user = User::find($recipient);
            $tokens[] = $user->web_fcm_token ? $user->web_fcm_token : null;
            $tokens[] = $user->app_fcm_token ? $user->app_fcm_token : null;
        }

        $tokens = array_values(array_filter(array_unique($tokens)));

        $fcmData = [
            "body" => [
                "id" => $notification->id,
                "title" => $notification->title,
                "description" => $notification->description,
                "created_at" => $notification->created_at,
                "type" => $notification->type,
                "module_id" => $notification->module_id,
                "module" => $notification->module
            ],
            "deviceTokens" => $tokens
        ];

        event(new SendFcmNotification($fcmData));
    }
}
