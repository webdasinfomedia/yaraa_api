<?php

namespace App\Jobs;

use App\Models\User;

class VoiceCommandUpdatePreferenceSaveJob extends Job
{
    public $subModule;
    public $lang;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($subModule, $lang)
    {
        $this->subModule = $subModule;
        $this->lang = $lang;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::all();
        $users->each(function ($user) {
            $voice_command_updated[] = [
                "sub_module" => $this->subModule,
                "lang" => $this->lang,
            ];
            $preferences = $user->preferences;
            if ($preferences) {
                if ($preferences->voice_command_updated != null) {
                    $oldPreference = json_decode($preferences->voice_command_updated, true);
                    $preferences->voice_command_updated = json_encode(array_merge($oldPreference, $voice_command_updated));
                } else {
                    $preferences->voice_command_updated =  json_encode($voice_command_updated);
                }
                $preferences->save();
            } else {
                $user->preferences()->create([
                    "voice_command_updated" => json_encode($voice_command_updated)
                ]);
            }
        });
    }
}
