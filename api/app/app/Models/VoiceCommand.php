<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VoiceCommand extends Model
{
    protected $fillable = [
        'command',
        'sub_module',
        'lang'
    ];
}
