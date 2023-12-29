<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MongoDB\Client;

class VoiceCommandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** Import CSV file as array **/
        // $path = base_path('assets' . DIRECTORY_SEPARATOR . 'voice_commands.csv');
        // if (file_exists($path) && \App\Models\VoiceCommand::count() == 0) {
        //     $data = array_map('str_getcsv', file($path));

        //     /** Bulk insert into db **/
        //     // $mongoClient = new Client();
        //     $connectionString = "mongodb://" . env('DB_USERNAME') . ":" . env('DB_PASSWORD') . "@" . env('DB_HOST') . ":" . env('DB_PORT') . "";
        //     $client = new \MongoDB\Client($connectionString);
        //     $db = app()->tenant->database;
        //     $collection = $mongoClient->{$db}->{'voice_commands'};
        //     $collection->insertMany($data);
        // }
    }
}
