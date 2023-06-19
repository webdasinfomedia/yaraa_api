<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Project;
use Hamcrest\Arrays\IsArray;
use Illuminate\Console\Command;

class addArchivedByColumn extends Command
{
    private $_tasks;
    private $_projects;
    private $_count = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:addArchivedColumn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add archived_by column to tasks & project table if its missing.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->_tasks = Task::withTrashed()->withArchived();
        $this->_projects = Project::all();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->_projects->each(function ($project) {
            if(!is_array($project->archived_by)){
                $project->archived_by = [];
                $project->save();
                $this->_count++;
            }
        });
        echo $this->_count ." Project Processed.\n";
        $this->_count = 0;

        $this->_tasks->each(function ($task) {
            if(!is_array($task->archived_by)){
                $task->archived_by = [];
                $task->save();
                $this->_count++;
            }
        });
        echo $this->_count ." Task Processed.\n";
        $this->_count = 0;
    }
}
