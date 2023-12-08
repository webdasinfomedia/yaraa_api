<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('mychannel', function ($user){
    // dump($user);
    return true;
});

Broadcast::channel('conversation_{conversation_id}', function ($user, $conversation_id){
    $conversation = \App\Models\Conversation::find($conversation_id);
    if($conversation){
        if($conversation->members->contains($user->id)){
            return ["user" => $user->email, "name" => $user->name];
        }
    }
});

Broadcast::channel('message_list', function ($user){
    return ["email" => $user->email, "name" => $user->name];
});


// $app[Illuminate\Contracts\Broadcasting\Factory::class]
//     ->channel('test.{userID}', function ($user, $userID) {
//         return $user->id === $userID;
//     });