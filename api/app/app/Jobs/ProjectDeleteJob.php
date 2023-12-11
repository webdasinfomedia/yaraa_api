<?php

namespace App\Jobs;

use App\Models\Project;

class ProjectDeleteJob extends Job
{
    public $project;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->project->processDelete();
    }
}
