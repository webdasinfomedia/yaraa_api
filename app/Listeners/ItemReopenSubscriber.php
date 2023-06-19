<?php

namespace App\Listeners;

use App\Events\MilestoneReopenEvent;
use App\Jobs\CreateActivityJob;
use App\Models\Task;
use App\Models\SubTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemReopenSubscriber
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
     * Handle project item reopen events.
     */
    public function handelReopenProjectItem($event)
    {
        if ($event->projectItem instanceof Task) {

            /** Create Activity Log and Notification **/
            if($event->projectItem->status == 're-open'){
                $this->createTaskReopenActivity($event->projectItem);
            }

            $this->reOpenMilestone($event->projectItem);
            $this->reopenProject($event->projectItem);
        }

        if ($event->projectItem instanceof SubTask) {
            $this->reOpenTask($event->projectItem->parentTask);
            $this->reOpenMilestone($event->projectItem->parentTask);
        }

        // if($event->projectItem instanceof Task)
        // {
        //     $this->reopenProject($event->projectItem);
        // }
    }

    private function reOpenMilestone(Task $task)
    {
        $task->milestones->each(function ($milestone) use ($task) {
            if ($milestone->end_date != null && $task->end_date == null) {
                $milestone->markAsReopen();
                event(new MilestoneReopenEvent($milestone));
            }
        });
    }

    private function reOpenTask(Task $task)
    {
        if ($task->end_date != null) {
            $task->markAsReopen();

            /** Create Activity Log and Notification **/
            $this->createTaskReopenActivity($task);

            // event(new ReopenProjectItemEvent($task)); //reopen milestone, project
            $this->reOpenProject($task);
        }
    }

    private function reopenProject(Task $task)
    {
        // if($task->project()->exists() && $task->project->end_date != null && ($task->status == 're-open' || $task->status == 'pending'))
        if ($task->project()->exists() && $task->project->end_date != null) {
            /** Create activity log ***/
            $activityData = [
                "activity" => "Project {$task->project->name} Reopened",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["project_id" => $task->project->id]),
                "activity" => "project_reopened",
            ];

            dispatch(new CreateActivityJob($activityData));

            $task->project->markAsReopen();
        }
    }

    /**
     * Handel task unarchived events.
     */
    public function handelTaskUnarchived($event)
    {
        if ($event->task->end_date == null) {
            /** Re-open milestones ****/
            $this->reOpenMilestone($event->task);
            // $event->task->milestones->each(function($milestone){
            //     if($milestone->end_date != null){
            //         $milestone->markAsReopen();
            //     }
            // });

            /** Re-open project ****/
            $this->reopenProject($event->task);
            // if($event->task->project->end_date != null){
            //     $event->task->project->markAsReopen();
            // }
        }
    }

    public function handelMilestoneReopen($event)
    {
        /** Create activity log and create chat group, notification & FCM notification */
        $activityData = [
            "activity" => "Milestone {$event->milestone->title} Reopened",
            "activity_by" => auth()->id(),
            "activity_time" => Carbon::now(),
            "activity_data" => json_encode(["project_id" => $event->milestone->project_id, 'milestone_title' => $event->milestone->title, 'author_name' => auth()->user()->name, "milestone_id" => $event->milestone->id]),
            "activity" => "milestone_reopened",
        ];

        dispatch(new CreateActivityJob($activityData));
    }

    private function createTaskReopenActivity($task)
    {
        $activityData = [
            "activity" => "Task {$task->name} Reopened",
            "activity_by" => auth()->id(),
            "activity_time" => Carbon::now(),
            "activity_data" => json_encode(["task_id" => $task->id, 'author_name' => auth()->user()->name]),
            "activity" => "task_reopened",
        ];

        dispatch(new CreateActivityJob($activityData));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        return [
            \App\Events\ReopenProjectItemEvent::class => 'handelReopenProjectItem',
            \App\Events\TaskUnarchived::class => 'handelTaskUnarchived',
            \App\Events\MilestoneReopenEvent::class => 'handelMilestoneReopen',
        ];
    }
}
