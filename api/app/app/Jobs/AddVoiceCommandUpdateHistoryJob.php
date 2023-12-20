<?php

namespace App\Jobs;

use App\Models\VoiceCommandUpdateHistory;

class AddVoiceCommandUpdateHistoryJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $updated;

    public function __construct($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        VoiceCommandUpdateHistory::create([
            "updated" => $this->updated,
        ]);
    }
}
