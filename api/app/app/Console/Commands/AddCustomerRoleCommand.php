<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class AddCustomerRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:create_customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Customer Roles';

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
        $tenants = Tenant::all();

        $tenants->each(function ($tenant) {
            $tenant->configure()->use();


            $exists =  \App\Models\Role::where('slug', 'customer')->exists();
            if (!$exists) {
                \App\Models\Role::create([
                    'name' => 'Customer',
                    'slug' => 'customer',
                    'permission' => null,
                    'is_Admin' => false,
                ]);

                $this->line("-----------------------------------------");
                $this->info("Customer role added for Tenant #{$tenant->id} ({$tenant->business_name})");
                $this->line("-----------------------------------------");
            }
        });
    }
}
