<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PusherMessageSend extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $payLoad;
    private $conversationId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($payLoad, $conversationId)
    {
        $this->payLoad = $payLoad;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('conversation_'.$this->conversationId);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
