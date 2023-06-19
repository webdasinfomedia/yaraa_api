<?php

use App\Models\VoiceCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CreateVoiceCommandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_commands', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        try {
            //import csv
            $vcCommandCsv = 'vc_languages' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . "en.csv";

            if (Storage::disk('local')->exists($vcCommandCsv)) {
                $csvFile = Storage::disk('local')->path($vcCommandCsv);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 15000, ",")) !== FALSE) {
                    VoiceCommand::create([
                        'command' => $data[0],
                        'lang' => $data[1],
                        'sub_module' => $data[2],
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::debug("VC_ERROR : " . json_encode($e->getMessage()));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voice_commands');
    }
}
