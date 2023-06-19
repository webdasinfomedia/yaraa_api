<?php

namespace App\Traits;

use App\Exceptions\TaskActivityException;
use DateTime;
use Carbon\CarbonInterval;

/**
 * 
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model start()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model complete()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model pause()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model resume()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model isStarted()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model isPaused()
 * @method public public|\Jenssegers\Mongodb\Eloquent\Model getMyTotalWorkHours()
 */
trait TaskActivity
{
    use TaskActivityLog;

    /**
     * start task for current user
     * mark task as started globally if not started
     * 
     * @return void
     */
    public function start()
    {
        if($this->isStarted()){
            throw new TaskActivityException('Task Already Started.');
        }

        $this->markAsStart();
        
        $taskDetails = $this->myDetails()->create([
            "user_id" => auth()->id(),
            "start_date" => new DateTime(),
            "end_date" => null,
            "status" => "in progress"
        ]);

        $taskDetails->dailyActivity()->create([
            "resume_date" => new DateTime(),
            "pause_date" => null,
        ]);

        /** TaskActivityLog Trait on Task Model ****/
        $this->createMessageActivity('resume'); 

    }

    /**
     * complete task for current user
     * 
     * @return void
     */
    public function complete()
    {
        if( ! $this->isStarted()){
            throw new TaskActivityException('Please Start The Task First.');
        }

        $this->pause();
        
        $this->myDetails->markAsComplete();
    }

    /**
     * pause task for current user
     * 
     * @return void
     */
    public function pause()
    {
        if( ! $this->isStarted()){
            throw new TaskActivityException('Please Start The Task First.');
        }

        if( ! $this->isPaused())
        {
           $this->myDetails->dailyActivity->whereNotNull('resume_date')->whereNull('pause_date')->first()->update([
                "pause_date" => new DateTime(),
            ]);

            $this->details()->update(['status' => 'paused']);
            
            /** TaskActivityLog Trait on Task Model ****/
            $this->createMessageActivity('pause');
        }
    }

    /**
     * resume task for current user
     * 
     * @return void
     */
    public function resume()
    {
        if(! $this->isStarted()){
            throw new TaskActivityException('Please Start The Task First.');
        }

        if(! $this->isPaused()){
            throw new TaskActivityException('Task Already on Going.');
        }

       $this->myDetails->dailyActivity()->create([
            "resume_date" => new DateTime(),
            "pause_date" => null,
        ]);

        $this->details()->update(['status' => 'in progress']);

        /** TaskActivityLog Trait on Task Model ****/
        $this->createMessageActivity('resume');
    }

    /**
     * check task for current user is started
     * 
     * @return boolean
     */
    public function isStarted()
    {
        return $this->myDetails()->whereNotNull('start_date')->count();
    }

    /**
     * check task for current user is paused
     * 
     * @return boolean
     */
    public function isPaused()
    {
        return $this->myDetails->dailyActivity->whereNotNull('resume_date')->whereNull('pause_date')->count() > 0
                ? false
                : true;
    }

    /**
     * get total work hours of current user
     * 
     * @return array
     */
    public function getMyTotalWorkHours()
    {
        if(! $this->myDetails()->exists()){
            return null;
        }
        
        $totalSeconds = $this->myDetails->dailyActivity->reduce(function($totalSeconds, $log) {
            return $totalSeconds + $log->resume_date->diffInSeconds($log->pause_date);
        });

        $totalTime = CarbonInterval::seconds($totalSeconds)->cascade();

        return [
            "days" => $totalTime->d,
            "hours" => $totalTime->h,
            "minutes" => $totalTime->i,
            "seconds" => $totalTime->s,
            "pretty_format" =>$totalTime->forHumans(true)
        ];
    }

}
