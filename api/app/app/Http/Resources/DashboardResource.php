<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $taskCollection = $this->orderWith()->getTasks('all'); //$this = current user, default orderWith = new_first
        $taskObj = new Task;

        return [
            "recently_assigned_task" => TaskListResource::collection($taskCollection->whereNull('end_date')),
            "todays_task" => TaskListResource::collection($taskObj->getTodaysPendingTask($taskCollection)),
            "recent_projects" => ProjectListResource::collection($this->orderWith()->getProjects('all')),
        ];
    }
}
