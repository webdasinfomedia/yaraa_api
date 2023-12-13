<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBasicResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'about_me' => $this->about_me,
            'designation' => $this->designation,
            'role' => ($this->role) ? $this->role->slug : null,
            'image' => $this->image ? url('storage/' . $this->image, true) : null,
            'image_thumb' => $this->image_48x48 ? url('storage/' . $this->image_48x48, true) : null,
            'is_admin' => $this->isAdmin()
        ];
    }
}
