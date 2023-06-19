<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VoiceCommandModule extends Model
{
    protected $fillable = [
        'title',
        'module',
        'lang'
    ];

    // public function commands()
    // {
    //     return $this->hasMany(VoiceCommand::class,'module','module');
    // }

    public function subModules()
    {
        return $this->hasMany(VoiceCommandSubModule::class, 'module', 'module');
    }
}
