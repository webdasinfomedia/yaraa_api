<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectListResource extends JsonResource
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
            // 'tags' => $this->tags->pluck('name'),
            'recently_assigned_task' => ProjectListTaskResource::collection(auth()->user()->tasks()->where('project_id', $this->id)->whereNull('end_date')->orderBy('created_at', 'desc')->limit(1)->get()),
            'attachments' => $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            // 'milestones' => $this->milestones ? MilestoneResource::collection($this->milestones) : null,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'status' => $this->currentStatus(),
            'progress_percent' => $this->getProgress(), //ProjectProgressible trait function
            'is_favourite' => $this->favourite_by ? (in_array(auth()->user()->id, $this->favourite_by) ? true : false) : false,
            "owner" => $this->owner ? new UserBasicResource($this->owner) : null,
            "search_type" => $this->type ? $this->type : null,
        ];
    }
}
