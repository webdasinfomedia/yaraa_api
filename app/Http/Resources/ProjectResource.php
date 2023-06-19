<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
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

        //Super Admin may see all tasks.
        // $task = all tasks
        $tasks = auth()->user()->tasks()->where('project_id', $this->id);

        $this->members->transform(function ($member) {
            $userRole = $this->roles()->where('user_id', $member->id)->pluck('role')->first();
            $member->projectRole = $userRole ?? null;
            return $member;
        });

        return  [
            'id' => $this->id,
            'name' => $this->name,
            'visibility' => $this->visibility,
            'board_view' => $this->board_view,
            'image' => $this->image ? url('storage/' . $this->image) : null,
            'description' => $this->description,
            'members' => $this->members ? MemberResource::collection($this->members) : null,
            'members_roles' => $this->members ? MemberResource::collection($this->members) : null,
            'tags' => $this->tags->pluck('name'),
            'recently_assigned_task' => TaskListResource::collection(auth()->user()->tasks()->where('project_id', $this->id)->whereNull('end_date')->orderBy('created_at', 'desc')->get()),
            // 'todays_task' => TaskListResource::collection($this->getTodaysPendingTask($this->tasks)),//method defined in TaskFilterable trait
            'todays_task' => TaskListResource::collection($this->getTodaysPendingTask($tasks->get())), //method defined in TaskFilterable trait
            'completed_task' => TaskListResource::collection($tasks->whereNotNull('end_date')->orderBy('end_date', 'desc')->get()),
            'attachments' => $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            'milestones' => $this->milestones ? MilestoneResource::collection($this->milestones) : null,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'status' => $this->currentStatus(),
            'is_favourite' => $this->favourite_by ? (in_array(auth()->user()->id, $this->favourite_by) ? true : false) : false,
            'created_at' => $this->created_at,
            "owner" => $this->owner ? new UserBasicResource($this->owner) : null,
            "conversation_id" => $this->conversation->id ?? null,
            "customers" => $this->customers->isNotEmpty() ? CustomerListResource::collection($this->customers) : [],
        ];
    }
}
