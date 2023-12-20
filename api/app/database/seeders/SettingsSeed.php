<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\Models\Setting::count() == 0) {

            \App\Models\Setting::create([
                "type" => "apps",
                "enabled_apps" => [],
            ]);

            \App\Models\Setting::create([
                "type" => "customer",
                "is_enabled" => false
            ]);

            \App\Models\Setting::create([
                "type" => "enabled_vc_languages",
                "languages" => ['en']
            ]);
        }
    }
}
