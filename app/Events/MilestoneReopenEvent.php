<?php

namespace App\Events;

use App\Models\Milestone;
use App\Models\Task;

class MilestoneReopenEvent extends Event
{
    public $milestone;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Milestone $milestone)
    {
        $this->milestone = $milestone;
    }
}
