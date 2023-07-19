<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopPerformanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $task = $this->projectTasks();
        
        return [
            "name" => $this->name,
            "email" => $this->email,
            "total_tasks" => $this->projectTasks()->count(),
            "total_completed_task" => $this->projectTasks()->where('status', 'completed')->count(),
            "ontime_completed_task" => $task->where('status', 'completed')->filter(function ($task) {
                if (!is_null($task->end_date) && $task->end_date <= $task->due_date) {
                    return true;
                }
                return false;
            })->count(),
        ];
    }
}
