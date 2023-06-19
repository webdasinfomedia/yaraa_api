<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\UserApp;

class SyncZoomTokenAcrossTenantJob extends Job
{
    public $token;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // return true;

        Tenant::all()->each(function ($tenant) {

            $tenant->configure()->use();

            // $settings = Setting::where('type', 'zoom_token')->exists();

            // if ($settings) {
            // Setting::where('type', 'zoom_token')
            //     ->update([
            //         'access_token' => $this->token['access_token'],
            //         'refresh_token' => $this->token['refresh_token'],
            //         'scope' => $this->token['scope'],
            //     ]);

            UserApp::where('type', 'zoom_token')
                ->update([
                    'access_token' => $this->token['access_token'],
                    'refresh_token' => $this->token['refresh_token'],
                    'scope' => $this->token['scope'],
                ]);
            // }
        });
    }
}
