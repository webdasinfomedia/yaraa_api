<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VoiceCommandSubModule extends Model
{
    protected $fillable = [
        'title',
        'module',
        'sub_module',
        'lang'
    ];

    public function commands()
    {
        return $this->hasMany(VoiceCommand::class, 'sub_module', 'sub_module');
    }
}
