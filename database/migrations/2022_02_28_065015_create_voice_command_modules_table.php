<?php

use App\Models\VoiceCommandModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CreateVoiceCommandModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_command_modules', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        try {


            //import csv
            $vcModuleFile = 'vc_module.csv';

            if (Storage::disk('local')->exists($vcModuleFile)) {
                $csvFile = Storage::disk('local')->path($vcModuleFile);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 100, ",")) !== FALSE) {
                    VoiceCommandModule::create([
                        'lang' => $data[0],
                        'module' => $data[1],
                        'title' => $data[2],
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
        Schema::dropIfExists('voice_command_modules');
    }
}
