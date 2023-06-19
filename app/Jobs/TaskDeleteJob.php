<?php

namespace App\Jobs;

use App\Models\Task;
use App\Traits\TaskDeletable;

class TaskDeleteJob extends Job
{
    // use TaskDeletable;

    public $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->task->processDelete();
    }
}
