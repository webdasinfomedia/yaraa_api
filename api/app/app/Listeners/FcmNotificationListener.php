<?php

namespace App\Listeners;

use App\Events\SendFcmNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class FcmNotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  SendFcmNotification  $event
     * @return void
     */
    public function handle(SendFcmNotification $event)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = json_encode(['registration_ids' => array_values(Arr::wrap($event->data['deviceTokens'])), 'data' => $event->data['body']]);

        $serverKey = config('services.fcm.server_key');
        // Firebase API Key
        $headers = array('Authorization:key=' . $serverKey, 'Content-Type:application/json');
        // Open connection
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        // if ($result === FALSE) {
        //     die('Curl failed: ' . curl_error($ch));
        // } else {
        //     dump($result);
        // }
        curl_close($ch);
    }
}
