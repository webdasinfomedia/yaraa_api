<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchiveList extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'archived_at' => $this->archived_at ? $this->archived_at->diffForhumans(null,0,1) : null,
            'type' => $this->type,
            'is_deleting' => $this->is_deleting,
            "search_type" => $this->type ? $this->type : null,
        ];
    }

}
