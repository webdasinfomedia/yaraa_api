<?php

namespace App\Listeners;

use App\Events\TaskCompletedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskCompletedListener implements ShouldQueue
{
    // use InteractsWithQueue;

    public $task;

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
     * @param  TaskCompletedEvent  $event
     * @return void
     */
    public function handle(TaskCompletedEvent $event)
    {
        $this->task = $event->task;
        $this->autoCompleteMilestone();
    }

    private function autoCompleteMilestone()
    {
        $completeMilestones = $this->task->milestones->filter(function($milestone){
            return $milestone->tasks->whereNull('end_date')->isEmpty();  //this deep relation not working in queues
        });

        $completeMilestones->each->markAsComplete();
    }
}
