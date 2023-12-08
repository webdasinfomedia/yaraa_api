<?php

namespace App\Traits;

use App\Models\Conversation;
use App\Models\DeletedRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait ProjectDeletable
{
    public function processDelete()
    {
        Log::info("Deleting Project : {$this->name}");

        /** Save to deleted records  ***/
        $this->saveToDeleteRecords();

        /** Delete Activity Logs. ***/


        /**  Unassign all tags ***/
        $this->detachTags();

        /**  Delete all tasks permanently ***/
        $this->tasks()->withTrashed()->withArchived()->get()->each->processDelete();

        /** Remove from favourite list of users. ***/
        $this->removeFromFavourite();

        /** Delete Milestones and attachments ***/
        $this->removeMilestones();

        /** Detach all members ***/
        $this->detachMembers();

        /** Delete all attachments ***/
        $this->deleteAttachments($this->attachments);

        /** Delete Project chat Group */
        // $this->deleteChatGroup();

        /*** Delete Project permanently ***/
        $this->removeRoles();

        /*** Delete Project permanently ***/
        $this->forceDelete();
    }

    private function detachTags()
    {
        $tagsId = $this->tags->pluck('id')->toArray();
        if (!empty($tagsId)) {
            $this->tags()->detach($tagsId);
            Log::info('Tags deleted : ' . implode(', ', $tagsId));
        }
    }

    private function removeFromFavourite()
    {
        $this->members->each(function ($member) {
            if ($member->favourite_projects()->exists()) {
                $member->favourite_projects()->detach($this->id);
                Log::info('Removed as favourite from user  : ' . $member->email);
            }
        });
    }

    private function removeMilestones()
    {
        if ($this->milestones->isNotEmpty()) {
            $this->milestones->each(function ($milestone) {
                $this->deleteAttachments($milestone->attachments);
                Log::info('Milestone Deleted : ' . $milestone->id);
                $milestone->delete();
            });
        }
    }

    private function detachMembers()
    {
        if ($this->members->isNotEmpty()) {
            $memberIds = $this->members->pluck('id')->toArray();
            if (!empty($memberIds)) {
                $this->members()->detach($memberIds);
                // Log::info('Members removed from project : '. implode(', ', $memberIds));
            }
        }
    }

    /**
     * delete files from directory
     * 
     * @return void
     */
    private function deleteAttachments($deletableFiles)
    {
        if (!empty($deletableFiles)) {
            foreach ($deletableFiles as $file) {
                Storage::disk('public')->delete('public/' . $file);
                Storage::delete('public/' . $file);

                Log::info('Deleted Files  : ' . $file);
            }
        }
    }

    private function saveToDeleteRecords()
    {
        DeletedRecord::create([
            "model" => "Project",
            "name" => $this->name,
            "data" => $this->toJson(),
        ]);
    }

    // private function deleteChatGroup()
    // {
    //     if($this->conversation->exists() && $this->conversation->members->isNotEmpty()){
    //         $memberIds = $this->conversation->members->pluck('id')->toArray();
    //         if(!empty($memberIds)){
    //             $this->conversation->members()->detach($memberIds);
    //             Log::info('Members removed from project chat group: '. implode(', ', $memberIds));

    //         }
    //     }

    //     $this->conversation->messages->each->delete();

    //     $this->conversation->delete();
    // }

    public function removeRoles()
    {
        $this->roles->each->delete();
    }
}
