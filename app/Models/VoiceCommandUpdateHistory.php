<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VoiceCommandUpdateHistory extends Model
{
    protected $fillable = [
        "sub_module",
        "lang",
    ];
}
