<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\Tenant;
use Illuminate\Console\Command;

class AddProjectDefaultRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:default_role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default role for project members';

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
            $this->line("-----------------------------------------");
            $this->info("Update Users for Tenant #{$tenant->id} ({$tenant->business_name})");
            $this->line("-----------------------------------------");

            Project::withTrashed()->withArchived()->each(function ($project) {
                if ($project->members->isNotEmpty()) {
                    $project->members->each(function ($member) use ($project) {
                        ProjectRole::updateOrCreate(
                            ["project_id" => $project->id, "user_id" => $member->id],
                            ["role" => Project::CAN_EDIT]
                        );

                        $this->line("-----------------------------------------");
                        $this->info("Added role in project #{$project->name} for user ({$member->email})");
                        $this->line("-----------------------------------------");
                    });
                }
            });
        });
    }
}
