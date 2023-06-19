<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Console\Command;

class DeleteEmptyMemberTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:remove_empty_members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove tasks, which does not have any members';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Tenant::all()->each(function ($tenant) {

            $tenant->configure()->use();

            Task::all()->each(function ($task) {
                if ($task->assignedTo->isEmpty()) {
                    $task->processDelete();
                }
            });
        });
    }
}
