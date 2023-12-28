<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;

class PusherEvent extends Event implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $text;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($text)
    {

        $this->text = $text;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('mychannel');
    }

    public function broadcastAs()
    {
        return 'status-update';
    }
}
