<?php

namespace App\Jobs;

use App\Models\User;

class TestJob extends Job
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
        $user = User::where('email', 'priyal@abc.com')->first();
        $user->name = 'Priyal Patel';
        $user->save();

    }
}
