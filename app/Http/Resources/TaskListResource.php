<?php

namespace App\Http\Resources;

use App\Models\Priority;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $urlPrefix = function ($image) {
            return $image ? urldecode(url('storage', $image)) : null;
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'assignee' =>  $this->assignedTo->pluck('email'),
            'assignee_images' => $this->assignedTo ? array_map($urlPrefix, $this->assignedTo()->pluck('image')->toArray()) : null,
            'priority' => $this->priority,
            'priority_color' => Priority::getColor($this->priority),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'attachments' => $this->attachments ? sizeof($this->attachments) : 0,
            'progress_percent' => $this->getProgress(),
            'project' => [
                "id" => $this->project->id ?? null,
                "name" => $this->project->name ?? null,
                'due_date' => $this->due_date,
                'role' => $this->project()->exists() ? ($this->project->roles()->where('user_id', auth()->id())->first()->role ?? null) : 'can_edit',
            ],
            "status" => $this->status,
            "completed_at" => $this->end_date,
            "is_mytask" => $this->assignee ? (in_array(auth()->id(), $this->assignee) ? true : false) : false,
            "personal_status" => $this->myDetails->status ?? 'pending',
            "my_total_work_hours" => $this->getMyTotalWorkHours(),
            "owner" => $this->owner ? new UserBasicResource($this->owner) : null,
            "search_type" => $this->type ? $this->type : null,
            'reminder' => $this->reminder ? $this->reminder : null,
            'recurrence' => $this->recurrence ?? null,
            'is_recurring' => $this->task_id != null ? true : false,
            'created_at' => $this->created_at,
        ];
    }
}
