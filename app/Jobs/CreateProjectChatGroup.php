<?php

namespace App\Jobs;

use App\Facades\CreateDPWithLetter;
use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class CreateProjectChatGroup extends Job
{
    public $project;
    /**
    * Create a new job instance.
    *
    * @return void
    */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
    
    /**
    * Execute the job.
    *
    * @return void
    */
    public function handle()
    {
        /** Create Conversation Modal as Group ***/
        $conversation = new Conversation;
        $conversation->type = 'group';
        $conversation->name = $this->project->name;
        $conversation->last_message_at = null;
        $conversation->project_id = $this->project->id;
        $conversation->created_by = $this->project->created_by;
        
        /** Create Group Logo ***/
        if($this->project->image){
            $conversation->logo = $this->project->image;        
        }else{
            $imageName = 'group/logo/' . getUniqueStamp() . '.png';
            $path = 'public/' . $imageName;
            $img = CreateDPWithLetter::create($this->project->name);
            Storage::put($path, $img->encode());
            $conversation->logo = $imageName;        
        }
        $conversation->save();
        
        /** Add Group Members ***/
        if($this->project->members->isNotEmpty()){
            foreach($this->project->members as $userId){
                $conversation->members()->attach($userId);
            }
        }
    }
}
