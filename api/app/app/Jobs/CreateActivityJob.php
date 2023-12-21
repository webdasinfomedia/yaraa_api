<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use App\Models\Activity;
use App\Models\FailedActivities;
use App\Models\FailedActivity;

class CreateActivityJob extends Job
{
    protected $activityData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityData)
    {
        $this->activityData = $activityData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $activityLog = Activity::create($this->activityData);
            $this->activityData['activity_id'] = $activityLog->id;

            [$serviceName, $method] = explode("_", $activityLog->activity);
            $className = "App\\Services\\" . ucfirst($serviceName) . "Service";
            $classObject =  new $className;
            call_user_func(array($classObject, $method), $this->activityData);

        } catch (\Throwable $e) {
            FailedActivity::create(['error_data' => $e->getMessage(), 'activity_data'=>json_encode($activityLog)]);
        }
    }

}
