<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonalMessageListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $query = $this->messages()->whereNotIn('created_by', [auth()->id()])->whereNotIn('read_by', [auth()->id()]);
        $unReadMessagesCount = $query->count();
        $sender = $this->members()->whereNotIn('_id', [auth()->id()])->first();
        $lastMessage = $this->messages()->active()->orderBy('created_at', 'desc')->first()->body ?? '';
        $lastMessageAttachment = $this->messages()->active()->orderBy('created_at', 'desc')->first()->attachments ?? [];
        $isCustomer = false;
        if ($sender) {
            if ($sender->role != null) {
                $isCustomer =  $sender->role->slug == 'customer' ? true : false;
            } else {
                $isCustomer = false;
            }
        }
        return [
            'conversation_id' => $this->id,
            'name' => $sender ? $sender->name : 'Deleted User',
            'email' => $sender ? $sender->email : null,
            'image' => $sender ? url('storage/' . $sender->image) : null,
            'unread_messages' => $unReadMessagesCount,
            'last_message_at' => $this->last_message_at->diffForhumans(null, 0, 1) ?? null,
            'last_message' => $lastMessage == '' && !empty($lastMessageAttachment) ? 'Attachment' : $lastMessage,
            'is_customer' => $isCustomer,
        ];
    }
}
