<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            "title" => $this->title,
            "description" => $this->description,
            "is_read" =>  $this->read_by ? in_array(auth()->id(), $this->read_by) : false,
            "created_at" => $this->created_at,
            "type" => $this->type,
            "module_id" => $this->module_id,
            "module" => $this->module,
            "tags_keys" => $this->tags ? array_keys($this->tags) : null,
            "tags_values" => $this->tags ? array_values($this->tags) : null,
        ];
    }
}
