<?php

namespace App\Http\Resources;

use App\Models\Priority;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectListTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return  [
            'name' => $this->name,
            'description' => $this->description,
            'priority_color' => Priority::getColor($this->priority),
        ];
    }
}
