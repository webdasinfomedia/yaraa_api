<?php

namespace App\Jobs;

use App\Models\Conversation;
use Illuminate\Support\Facades\Storage;

class DeleteMessageHistoryJob extends Job
{
    public $conversationId;
    public $delete = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($conversationId)
    {
        $this->conversationId = $conversationId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $conversation = Conversation::find($this->conversationId);
        if ($conversation){
            $conversation->messages->each(function($message) use ($conversation){
                $match = array_diff($conversation->members->pluck('id')->toArray(),$message->deleted_by);
                if(!empty($match)){
                    $this->delete = false;
                }
            });

            if($this->delete){
                /** delete messages attachments **/
                $conversation->messages->each(function($message){
                    foreach ($message->attachments as $file)
                    {
                        if(Storage::disk()->exists('public/'.$file)){
                            Storage::disk('public')->delete('public/'.$file); 
                            Storage::delete('public/'.$file);
                        }
                    }
                });

                $conversation->messages->each->delete();
            }
        }
    }
}
