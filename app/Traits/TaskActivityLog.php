<?php

namespace App\Traits;

use DateTime;

trait TaskActivityLog
{
    /**
     * log task work activity to comments/message 
     * 
     * @return void
     */
    public function createMessageActivity($type) //pause //resume
    {
        $this->deleteTodaysPreviousActivity($type);

        $this->comments()->create([
            "message" => $type == 'resume' ? "Started working." : "Stopped working.",
            "type" => $type,
        ]);
    }

    /**
     * delete log of same type and of same day 
     * 
     * @return void
     */
    private function deleteTodaysPreviousActivity($type)
    {
        $todayStart = new DateTime(date('Y-m-d'));
        $todayEnd = new DateTime(date('Y-m-d').'+1 day');

        $this->comments()
            ->where('created_by',auth()->id())
            ->where('created_at', '>=' , $todayStart)
            ->where('created_at', '<' , $todayEnd)
            ->where('type', $type)
            ->delete();
    }
}
