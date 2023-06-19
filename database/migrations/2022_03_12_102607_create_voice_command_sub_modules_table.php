<?php

use App\Models\VoiceCommandSubModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CreateVoiceCommandSubModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_command_sub_modules', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        try {
            //import csv
            $vcModuleFile = 'vc_sub_module.csv';

            if (Storage::disk('local')->exists($vcModuleFile)) {
                $csvFile = Storage::disk('local')->path($vcModuleFile);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 100, ",")) !== FALSE) {
                    VoiceCommandSubModule::create([
                        'lang' => $data[0],
                        'module' => $data[1],
                        'sub_module' => $data[2],
                        'title' => $data[3],
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
        Schema::dropIfExists('voice_command_sub_modules');
    }
}
