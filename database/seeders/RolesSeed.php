<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // factory(App\Role::class, 10)->create();
        // \App\Models\Role::factory()->count(5)->create();

        if (\App\Models\Role::count() == 0) {
            \App\Models\Role::create([
                'name' => 'Admin',
                'slug' => 'admin',
                'permission' => null,
                'is_Admin' => true,
            ]);

            \App\Models\Role::create([
                'name' => 'Employee',
                'slug' => 'employee',
                'permission' => null,
                'is_Admin' => false,
            ]);

            \App\Models\Role::create([
                'name' => 'Customer',
                'slug' => 'customer',
                'permission' => null,
                'is_Admin' => false,
            ]);
        }
    }
}
