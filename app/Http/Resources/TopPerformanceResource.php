<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopPerformanceResource extends JsonResource
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
            "name" => $this->name,
            "email" => $this->email,
            "total_tasks" => $this->count(),
            "total_completed_task" => $this->where('status', 'completed')->count()
        ];
    }
}
