<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesSeed::class);
        $this->call(PrioritySeed::class);
        $this->call(NotificationSettingSeed::class);
        $this->call(SettingsSeed::class);
        // $this->call(VoiceCommandSeeder::class);
    }
}
