<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            "name" => $this->name,
            "email" => $this->email,
            "image" => url('storage/' . $this->image),
            "designation" => $this->designation,
            "is_disabled" => $this->trashed() ? true : false,
        ];

        if (key_exists('projectRole', $this->resource->toArray())) {
            $response = array_merge($response, ["role" => $this->projectRole]);
        }

        return $response;
    }
}
