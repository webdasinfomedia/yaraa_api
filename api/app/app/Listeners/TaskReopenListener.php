<?php

namespace App\Listeners;

use App\Models\Task;
use App\Models\SubTask;
use App\Events\ReopenProjectItemEvent;
use Illuminate\Queue\InteractsWithQueue;

class TaskReopenListener
{
    // use InteractsWithQueue;

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
        if($event->projectItem instanceof SubTask){
            $this->reOpenTask($event->projectItem->parentTask);
        }
    }

    /**
     * Reopen Task if completed
     * 
     * Fire event to listen for miletone reopen
     * 
     * @param Task
     * @return void
     */
    private function reOpenTask(Task $task)
    {
        if($task->end_date != null)
        {
            $task->markAsReopen();
            
            event(new ReopenProjectItemEvent($task)); //reopen milestone, project
        } 
    }
}
