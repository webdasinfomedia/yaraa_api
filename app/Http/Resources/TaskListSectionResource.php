<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $taskCollection = $this->orderWith($request->order_by)->getTasks($request->task_by); //$this = current user
        $todoCollection = $this->orderWith($request->order_by)->getTodos($request->task_by);
        $taskObj = new Task;

        return [
            "recently_assigned_task" => TaskListResource::collection($taskCollection->whereNull('end_date')),
            "todays_task" => TaskListResource::collection($taskObj->getTodaysPendingTask($taskCollection)),
            "unassigned_tasks" => TaskListResource::collection($taskCollection->whereNull('project_id')->whereNull('end_date')),
            "recently_completed_task" =>  TaskListResource::collection($taskCollection->sortByDesc('end_date')->whereNotNull('end_date')),
            // "todos" => TodoResource::collection($todoCollection),
            "todos" => TodoListResource::collection($todoCollection),
        ];
    }
}
