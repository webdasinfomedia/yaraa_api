<?php

namespace App\Http\Resources;

use App\Models\Priority;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $urlPrefix = function ($file) {
            return url(Storage::url($file));
        };

        return  [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'assignee' => $this->assignedTo ? MemberResource::collection($this->assignedTo) : null,
            'priority' => $this->priority,
            'priority_color' => Priority::getColor($this->priority),
            'due_date' => $this->due_date,
            'visibility' => $this->visibility,
            'milestones' => $this->milestones ? MilestoneResource::collection($this->milestones) : null,
            'attachments' => $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            'tags' => $this->tags ? $this->tags->pluck('name') : null,
            'activity_logs' => $this->activity_logs,
            'status' => $this->status,
            'personal_status' => $this->myDetails->status ?? 'pending',
            'my_total_work_hours' => $this->getMyTotalWorkHours(),
            'project' => [
                "id" => $this->project->id ?? null,
                "name" => $this->project->name ?? null,
            ],
            'start_date' => $this->start_date,
            "completed_at" => $this->end_date,
            'subtasks' => $this->subtasks ? SubTaskResource::collection($this->subtasks) : null,
            "owner" => $this->owner ? new UserBasicResource($this->owner) : null,
            'reminder' => $this->reminder ? $this->reminder : null,
            'recurrence' => $this->recurrence ?? null,
            'is_recurring' => $this->task_id != null ? true : false,
        ];
    }
}
