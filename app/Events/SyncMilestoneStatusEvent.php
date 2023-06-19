<?php

namespace App\Events;

use App\Models\Project;

class SyncMilestoneStatusEvent extends Event
{
    public $project;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
}
