<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberTaskListSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $taskObj = new Task;
        $taskCollection = new Collection([$this->resource]);

        return [
            "recently_assigned_task" => TaskListResource::collection($taskCollection->whereNull('end_date')->take(3)),
            "todays_task" => TaskListResource::collection($taskObj->getTodaysPendingTask($taskCollection)),
            "unassigned_tasks" => TaskListResource::collection($taskCollection->whereNull('project_id')->whereNull('end_date')),
            "recently_completed_task" =>  TaskListResource::collection($taskCollection->whereNotNull('end_date')->take(3)),
        ];
    }
}
