<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
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
            "module" => $this->module,
            "status" => $this->status,
            "description" => $this->description,
            "created_by" => $this->author->name,
            "created_at" => $this->activity_at,
        ];
    }
}
