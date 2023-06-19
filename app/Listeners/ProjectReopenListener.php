<?php

namespace App\Listeners;

use App\Events\ReopenProjectItemEvent;
use App\Models\Task;

class ProjectReopenListener
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
     * @param  ReopenProjectItemEvent  $event
     * @return void
     */
    public function handle(ReopenProjectItemEvent $event)
    {
        if($event->projectItem instanceof Task)
        {
            $this->reopenProject($event->projectItem);
        }
    }

    private function reopenProject(Task $task)
    {
        if($task->project()->exists() && $task->project->end_date != null && ($task->status == 're-open' || $task->status == 'pending'))
        {
            $task->project->markAsReopen();
        }
    }
}
