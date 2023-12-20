<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskCommentResource extends JsonResource
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

        if ($this->type == 'zoom') {
            $zoomDetails = [
                'start_time' => $this->zoom_details['start_time'] ?? null,
                'meeting_id' => $this->zoom_details['meeting_id'] ?? null,
                'meeting_password' => $this->zoom_details['meeting_password'] ?? null,
                'meeting_url' => $this->created_by == auth()->id() ? $this->zoom_details['start_url'] : $this->zoom_details['join_url'],
            ];
        } else {
            $zoomDetails = null;
        }

        // $details = is_string($this->details) ? json_decode($this->details) : $this->details ?? null;
        return [
            "id" => $this->id,
            "posted_by" =>  new MemberResource($this->user),
            "message" => $this->message,
            "type" => $this->type,
            "attachments" =>  $this->attachments ? array_map($urlPrefix, $this->attachments) : null,
            "zoom_details" =>  $zoomDetails,
            "meet_details" =>  $this->meet_details ?? null,
            "location_details" =>  json_decode($this->location_details) ?? null,
            // "details" =>  $this->details ? json_decode($this->details) : null,
            // "details" =>  $details,
            "is_important" => is_array($this->marked_important_by) ? in_array(auth()->id(), $this->marked_important_by) : false,
            "created_at" => $this->created_at,
            "created_at_pretty" => $this->created_at->diffForhumans(),
        ];
    }
}
