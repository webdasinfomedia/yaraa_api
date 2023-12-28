<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\UserApp;

class EnableAppJob extends Job
{
    public $app;
    public $userId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($app, $userId)
    {
        $this->app = $app;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userApps = UserApp::where('user_id', $this->userId)->where('type', 'apps');

        if ($userApps->exists()) {
            $userApps = $userApps->first();
            if (!in_array($this->app, $userApps->enabled_apps)) {
                $userApps->push('enabled_apps', $this->app);
            }
        } else {
            UserApp::create([
                "user_id" => $this->userId,
                "type" => "apps",
                "enabled_apps" => [$this->app],
                "installed_apps" => [$this->app]
            ]);
        }
    }
}
