<?php

namespace App\Http\Resources;

use App\Models\SuperAdmin;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBasicResource extends JsonResource
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
            'name' => 'Yaraa Admin',
            'email' => $this->email,
            'about_me' => null,
            'designation' => null,
            'role' => 'super_admin',
            'image' => null,
            'image_thumb' => null,
            'is_admin' => null
        ];
    }
}
