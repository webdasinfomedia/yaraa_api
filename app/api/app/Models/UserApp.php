<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class UserApp extends Model
{
    protected $fillable = [
        "user_id",
        "type",
        "enabled_apps",
        "installed_apps",
        "access_token",
        "refresh_token",
        "scope",
    ];
}
