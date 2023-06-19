<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;

class UpdateUserFcmTokenJob extends Job
{
    public $request;
    public $userId;
    public $tenantId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $userId, $tenantId)
    {
        $this->request = $request;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // if (request()->has('is_web')) {
        //     if (request()->get('is_web') == "true") {
        //         $user->web_fcm_token = request()->get('fcm_token');
        //     } else {
        //         $user->app_fcm_token = request()->get('fcm_token');
        //     }
        // }

        if (array_key_exists('is_web', $this->request)) {
            Tenant::find($this->tenantId)->configure()->use();

            if ($this->request['is_web'] == "true") {
                User::where('web_fcm_token', $this->request['fcm_token'])->update(['web_fcm_token' => '']); // to avoid having same token to multiple users
                $user = User::find($this->userId);
                $user->web_fcm_token = $this->request['fcm_token'];
            } else {
                User::where('app_fcm_token', $this->request['fcm_token'])->update(['app_fcm_token' => '']);
                $user = User::find($this->userId);
                $user->app_fcm_token = $this->request['fcm_token'];
            }

            $user->save();
        }
    }
}
