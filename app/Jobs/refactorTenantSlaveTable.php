<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantSlaveUser;

class refactorTenantSlaveTable extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TenantSlaveUser::all();
        // $users = Users::groupBy('title')
        //     ->get(['title', 'name']);
    }
}
