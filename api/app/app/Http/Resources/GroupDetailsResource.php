<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GroupDetailsResource extends JsonResource
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
            "logo" => $this->logo ? url('storage/' . $this->logo) : null,
            "member" => UserBasicResource::collection($this->members)
        ];
    }
}
