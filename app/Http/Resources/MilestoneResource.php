<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MilestoneResource extends JsonResource
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

        return [
            "id" => $this->id,
            "title" => $this->title,
            "status" => $this->status,
            "description" => $this->description,
            "due_date" => $this->due_date,
            "attachments" => $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            "search_type" => $this->type ? $this->type : null,
        ];
    }
}
