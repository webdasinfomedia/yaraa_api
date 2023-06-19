<?php

namespace App\Listeners;

use App\Events\StartedProjectItem;
use App\Models\Task;

class StartMilestoneListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  StartedProjectItem  $event
     * @return void
     */
    public function handle(StartedProjectItem $event)
    {
        if($event->projectItem instanceof Task){
            $this->startMilestone($event->projectItem);
        }
    }

    private function startMilestone(Task $task)
    {
        $task->milestones->each(function($milestone){
            if($milestone->start_date == null)
            {
                $milestone->markAsStart();
            }
        });
    }
}
