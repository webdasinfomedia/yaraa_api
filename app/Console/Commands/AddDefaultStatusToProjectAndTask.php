<?php

namespace App\Console\Commands;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Console\Command;

class AddDefaultStatusToProjectAndTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:AddStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Default PENDING status to project,task and milestone models where status is missing.';

    private $_projects;
    private $_tasks;
    private $_milestones;
    private $_count = 0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->_projects = Project::all();
        $this->_tasks = Task::all();
        $this->_milestones = Milestone::all();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->_projects->each(function ($project) {
            if(!isset($project->status) || $project->status == null){
                $project->status = 'pending';
                $project->save();
                $this->_count++;
            }
        });
        echo $this->_count ." Project Processed.\n";
        $this->_count = 0;

        $this->_tasks->each(function ($task) {
            if(!isset($task->status) || $task->status == null){
                $task->status = 'pending';
                $task->save();
                $this->_count++;
            }
        });
        echo $this->_count ." Task Processed.\n";
        $this->_count = 0;

        $this->_milestones->each(function ($milestone) {
            if(!isset($milestone->status) || $milestone->status == null){
                $milestone->status = 'pending';
                $milestone->save();
                $this->_count++;
            }
        });

        echo $this->_count ." milestones Processed.\n";
    }
}
