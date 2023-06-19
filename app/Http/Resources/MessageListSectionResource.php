<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageListSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $personalList = $this->conversations()->active()->whereIn('member_ids',[auth()->id()])->where('type', 'personal')->orderBy('last_message_at','desc')->get();
        $groupList = $this->conversations()->active()->whereIn('member_ids',[auth()->id()])->where('type', 'group')->orderBy('last_message_at','desc')->get();

        return [
            'personal_Lists' => $personalList->isNotEmpty() ? PersonalMessageListResource::collection($personalList) : null,
            'group_list' => $groupList->isNotEmpty() ? GroupMessageListResource::collection($groupList) : null,
        ];
    }
}
