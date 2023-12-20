<?php

namespace App\Listeners;

use App\Events\MilestoneCompletedEvent;
use App\Jobs\CreateActivityJob;
use App\Models\Task;
use Carbon\Carbon;

class ItemCompleteSubscriber
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
     * Handel task archived events.
     */
    public function handelTaskArchived($event)
    {
        //complete milestone
        $this->completeMilestones($event->task);
    }

    public function handelTaskCompleted($event)
    {
        $activityData = [
            "activity" => "Task {$event->task->name} Completed",
            "activity_by" => auth()->id(),
            "activity_time" => Carbon::now(),
            "activity_data" => json_encode(["task_id" => $event->task->id, "author_name" => auth()->user()->name,]),
            "activity" => "task_completed",
        ];

        dispatch(new CreateActivityJob($activityData));

        $this->completeMilestones($event->task);
    }

    private function completeMilestones(Task $task)
    {
        $completeMilestones = $task->milestones->filter(function ($milestone) {
            return $milestone->tasks->whereNull('end_date')->isEmpty();
        });

        $completeMilestones->each(function ($milestone) {
            $milestone->markAsComplete();
            event(new MilestoneCompletedEvent($milestone));
        });
    }

    public function handelMilestoneCompleted($event)
    {
        /** Create activity log and create chat group, notification & FCM notification */
        $activityData = [
            "activity" => "Milestone {$event->milestone->title} Completed",
            "activity_by" => auth()->id(),
            "activity_time" => Carbon::now(),
            "activity_data" => json_encode(["project_id" => $event->milestone->project_id, 'milestone_title' => $event->milestone->title, "milestone_id" => $event->milestone->id]),
            "activity" => "milestone_completed",
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
            \App\Events\TaskArchived::class => 'handelTaskArchived',
            \App\Events\TaskCompletedEvent::class => 'handelTaskCompleted',
            \App\Events\MilestoneCompletedEvent::class => 'handelMilestoneCompleted',
        ];
    }
}
