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
        //     $mongoClient = new \MongoDB\Client("mongodb://admin:" . env('DB_PASSWORD') . "@127.0.0.1:27017");
        //     $db = app()->tenant->database;
        //     $collection = $mongoClient->{$db}->{'voice_commands'};
        //     $collection->insertMany($data);
        // }
    }
}
