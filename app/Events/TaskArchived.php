<?php

namespace App\Events;

use App\Models\Task;

class TaskArchived extends Event
{
    public $task;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
