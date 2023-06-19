<?php

namespace App\Http\Resources;

use App\Models\Priority;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TodoResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date ? $this->start_date->setTimezone(getUserTimezone())->toDateTimeString() : null,
            'reminder' => $this->reminder ? $this->reminder : null,
            'priority_color' => Priority::getColor($this->priority),
            'priority' => $this->priority,
            'visibility' => $this->visibility,
            'due_date' => $this->due_date,
            'attachments' =>  $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            "status" => $this->status,
            "completed_at" => $this->end_date,
            'recurrence' => $this->recurrence ?? null,
            'is_recurring' => $this->todo_id != null ? true : false,
        ];
    }
}
