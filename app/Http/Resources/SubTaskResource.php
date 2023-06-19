<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SubTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "assignee" => $this->assignee != null ? new UserBasicResource(User::find($this->assignee->id)) : null,
            "due_date" => $this->due_date,
            "status" => $this->status,
            "completed" => $this->completed_at
        ];
    }
}
