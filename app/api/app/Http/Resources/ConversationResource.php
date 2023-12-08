<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $messages = $this->messages()->active()->get();

        return [
            "conversation_id" => $this->id,
            'created_at' => $this->created_at->diffForhumans(null, 0, 1),
            "author" => $this->author->name ?? null,
            "members" => $this->members ? $this->members->pluck('email') : null,
            "messages" => $messages ? MessageResource::collection($messages) : null,
            "type" => $this->type,
        ];
    }
}
