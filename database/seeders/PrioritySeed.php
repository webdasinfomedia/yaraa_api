<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PrioritySeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (\App\Models\Priority::count() == 0) {

            \App\Models\Priority::create([
                'name' => 'High',
                'color' => '#FD0000',
                'slug' => 'high',
            ]);

            \App\Models\Priority::create([
                'name' => 'Medium',
                'color' => '#443A49',
                'slug' => 'medium',
            ]);

            \App\Models\Priority::create([
                'name' => 'Low',
                'color' => '#961BD4',
                'slug' => 'low',
            ]);
        }
    }
}
