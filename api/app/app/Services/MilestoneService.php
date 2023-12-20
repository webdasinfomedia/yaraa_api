<?php

namespace App\Services;

use App\Jobs\UpdateTimelineJob;
use App\Models\User;
use App\Models\Project;
use App\Models\Notification;
use App\Models\FailedActivity;
use App\Models\Timeline;
use App\Traits\FcmNotification;
use Carbon\Carbon;

class MilestoneService
{
    use FcmNotification;

    private $activity_data;
    private $project;

    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }


    public function created($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $project = Project::find($activity_data['project_id']);

        if (getNotificationSettings('milestone_created') || getNotificationSettings('milestone_created') == "true") {
            $notification = Notification::create([
                "title" => "Milestone Added",
                "description" => "New milestone {MILESTONE_TITLE} added in project {PROJECT_NAME} by {AUTHOR_NAME}",
                "receiver_ids" => $project->members->pluck('id')->toArray(),
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "milestone_created",
                "module_id" => null,
                "module" => "Project",
                "tags" => [
                    "MILESTONE_TITLE" => $activity_data['milestone_title'],
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "New milestone {$activity_data['milestone_title']} added in project {$project->name} by {$activity_data['author_name']}";
            $tokens = $project->members->pluck('fcm_token')->toArray();
            $this->sendFcmNotification($notification, $tokens);
        }

        $timelineFeed = [
            "module" => "milestone",
            "module_id" => $activity_data['milestone_id'],
            "project_id" => $activity_data['project_id'],
            "created_by" => $activity['activity_by'],
            "description" => "Milestone Created : {$activity_data['milestone_title']}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);
    }

    public function completed($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $project = Project::find($activity_data['project_id']);
        $authorId = User::find($activity['activity_by']);

        //store notification
        if (getNotificationSettings('milestone_completed') || getNotificationSettings('milestone_completed') == "true") {
            $notification = Notification::create([
                "title" => "Milestone Completed",
                "description" => "Congratulations!! Milestone {MILESTONE_TITLE} achieved in project {PROJECT_NAME}",
                "receiver_ids" => $project->members->pluck('id')->toArray(),
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "milestone_completed",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "MILESTONE_TITLE" => $activity_data['milestone_title'],
                    "PROJECT_NAME" => $project->name,
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Congratulations!! Milestone {$activity_data['milestone_title']} achieved in project {$project->name}";
            $tokens = $project->members->pluck('fcm_token')->toArray();
            $this->sendFcmNotification($notification, $tokens);
        }

        $timelineFeed = [
            "module" => "milestone",
            "module_id" => $activity_data['milestone_id'],
            "created_by" => $activity['activity_by'],
            "project_id" => $activity_data['project_id'],
            "description" => "Milestone Completed : {$activity_data['milestone_title']}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);
    }

    public function reopened($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $project = Project::find($activity_data['project_id']);
        $recipients = $project->members->pluck('id')->toArray();

        //store notification
        if (getNotificationSettings('milestone_reopen') || getNotificationSettings('milestone_reopen') == "true") {
            $notification = Notification::create([
                "title" => "Milestone Reopened",
                "description" => "Milestone {MILESTONE_TITLE} in project {PROJECT_NAME} is re-opened by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "milestone_reopened",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "MILESTONE_TITLE" => $activity_data['milestone_title'],
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Milestone {$activity_data['milestone_title']} in project {$project->name} is re-opened by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }

        $timelineFeed = [
            "module" => "milestone",
            "module_id" => $activity_data['milestone_id'],
            "created_by" => $activity['activity_by'],
            "project_id" => $activity_data['project_id'],
            "description" => "Milestone Re-opened : {$activity_data['milestone_title']}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);
    }

    public function deleted($activity)
    {
        $activity_data = json_decode($activity['activity_data'], true);
        $project = Project::find($activity_data['project_id']);
        $recipients = $project->members->pluck('id')->toArray();

        //store notification
        if (getNotificationSettings('milestone_deleted') || getNotificationSettings('milestone_deleted') == "true") {
            $notification = Notification::create([
                "title" => "Milestone Deleted",
                "description" => "Milestone {MILESTONE_NAME} from project {PROJECT_NAME} deleted by {AUTHOR_NAME}",
                "receiver_ids" => $recipients,
                "activity_id" => $activity['activity_id'],
                "ready_by" => [],
                "type" => "project_deleted",
                "module_id" => $project->id,
                "module" => "Project",
                "tags" => [
                    "MILESTONE_NAME" => $activity_data['milestone_title'],
                    "PROJECT_NAME" => $project->name,
                    "AUTHOR_NAME" => $activity_data['author_name'],
                ],
            ]);
        }

        if (getNotificationSettings('push') || getNotificationSettings('push') == "true") {
            $notification->description = "Milestone {$activity_data['milestone_title']} from project {$project->name} deleted by {$activity_data['author_name']}";
            $this->sendFcmNotification($notification, $recipients);
        }

        $timelineFeed = [
            "module" => "milestone",
            "module_id" => $activity_data['milestone_id'],
            "project_id" => $activity_data['project_id'],
            "created_by" => $activity['activity_by'],
            "description" => "Milestone Deleted : {$activity_data['milestone_title']}",
            "activity_at" => Carbon::now(),
        ];

        Timeline::create($timelineFeed);
    }
}
