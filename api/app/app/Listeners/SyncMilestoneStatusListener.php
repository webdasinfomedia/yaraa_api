<?php

namespace App\Listeners;

use App\Events\SyncMilestoneStatusEvent;
use App\Events\TaskCompletedEvent;

class SyncMilestoneStatusListener
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
     * @param  SyncMilestoneStatusEvent  $event
     * @return void
     */
    public function handle(SyncMilestoneStatusEvent $event)
    {
        $event->project->milestones->each(function($milestone){

            $milestone->tasks->each(function($task){
                event(new TaskCompletedEvent($task));
            });
            // if($milestone->end_date != null){
            //     $milestone->markAsComplete();
            // }
        });
    }
}
