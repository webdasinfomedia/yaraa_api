<?php

namespace App\Jobs;

use App\Models\Project;

class SyncGroupChatMember extends Job
{
    public $project;
    public $attachUsersIds;
    public $detachUsersIds;
    /**
    * Create a new job instance.
    *
    * @return void
    */
    public function __construct(Project $project, $attachUsersIds, $detachUsersIds)
    {
        $this->project = $project;
        $this->attachUsersIds = $attachUsersIds;
        $this->detachUsersIds = $detachUsersIds;
    }
    
    /**
    * Execute the job.
    *
    * @return void
    */
    public function handle()
    {
        $group = $this->project->conversation;
        
        if($group)
        {
            /** Add users to group/conversation */
            foreach($this->attachUsersIds as $userId){
                $group->members()->attach($userId);
            }
            
            /** Remove users from group/conversation */
            foreach($this->detachUsersIds as $userId){
                $group->members()->detach($userId);
            }

            $this->project->save();
        }else{
            /** Create chat Group if not exists */
            dispatch(new CreateProjectChatGroup($this->project));
        }
        
    }
}
