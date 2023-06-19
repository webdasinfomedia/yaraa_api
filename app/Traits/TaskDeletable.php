<?php

namespace App\Traits;

use App\Models\DeletedRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait TaskDeletable
{
    public function processDelete()
    {
        Log::info("Deleting Task : {$this->name}");
        
        /** Save to deleted records  ***/
        $this->saveToDeleteRecords();
        
        /** remove all activity logs **/

        /**  unassign all tags **/
        $this->detachTags();
        Log::info('Tags Detached ');
        Log::info('############### 10%');

        /** remove all comments and attachments **/
        $this->deleteComments();
        Log::info('Comments Deleted ');

        /** remove all users from subtasks and remove subtasks **/
        $this->deleteSubtasks();
        Log::info('Deleted all subtasks');

        /** unassign milestones **/
        $this->detachMilestones();
        Log::info('All Milestones Detached');

        /** remove attachments **/
        $this->deleteAttachments($this->attachments);
        Log::info('All Attachments deleted');
        
        /**  unassign users from task **/
        $this->detachAssignee();
        Log::info('All member un assigned');

        /**  remove task details **/
        $this->deleteTaskDetails();
        Log::info('Deleted Task Details');

        /**  delete task permanently **/
        $this->forceDelete();
        Log::info('Task Deleted Permanently');
    }

    /**
     * detach tags from task
     * 
     * @return void
     */
    private function detachTags()
    {
        $tagsId = $this->tags->pluck('id')->toArray();
        if(!empty($tagsId))
        {
            $this->tags()->detach($tagsId);
            Log::info('Tags deleted : ' . implode(', ', $tagsId));       
        }
    }

    /**
     * delete comments from task
     * 
     * @return void
     */
    private function deleteComments()
    {
        if($this->comments->isNotEmpty())
        {
            $deletedIds = [];
            $this->comments->each(function($comment) use($deletedIds){
                $this->deleteAttachments($comment->attachments);
                $deletedIds[] = $comment->id;
                $comment->delete();
            });
            Log::info('Comments deleted : ' . implode(', ', $deletedIds));
        }
    }

    /**
     * delete subtasks
     * 
     * @return void
     */
    private function deleteSubtasks()
    {
        if($this->subTasks->isNotEmpty()){
            $subtaskIds = $this->subTasks()->pluck('id')->toArray();
            Log::info('Subtask deleted : ' . implode(', ', $subtaskIds));
            $this->subTasks()->delete();
        }
    }

    /**
     * detach milestones
     * 
     * @return void
     */
    private function detachMilestones()
    {
        $milestoneIds = $this->milestones->pluck('id')->toArray();
        if (!empty($milestoneIds)) {
            Log::info('detached milestones : ' . implode(', ', $milestoneIds));
            $this->milestones()->detach($milestoneIds);
        }
    }

    /**
     * detach assignee
     * 
     * @return void
     */
    private function detachAssignee()
    {
        if($this->assignedTo->isNotEmpty()){
            $assigneeIds = $this->assignedTo->pluck('id')->toArray();
            if(!empty($assigneeIds)){
                Log::info('detached assignee : ' . implode(', ', $assigneeIds));
                $this->assignedTo()->detach($assigneeIds);
            }
        }
    }

    /**
     * delete task details
     * 
     * @return void
     */
    private function deleteTaskDetails()
    {
        if($this->details->isNotEmpty()){
            $taskDetailsId = $this->details->pluck('id')->toArray();
            Log::info('Deleted Task Details  : ' . implode(', ', $taskDetailsId));
            
            $this->details->each(function($detail){
                $detail->delete();
            });
        }
    }

    /**
     * delete files from directory
     * 
     * @return void
     */
    private function deleteAttachments($deletableFiles)
    {
        if(!empty($deletableFiles)){
            foreach ($deletableFiles as $file)
            {
                Storage::disk('public')->delete('public/'.$file); 
                Storage::delete('public/'.$file);

                Log::info('Deleted Files  : '. $file);
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
}
