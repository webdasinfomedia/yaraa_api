<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class PitchGroundCode extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        "code",
        "user_limit",
        "plan",
        "redeemed",
        "redeemed_at",
    ];

    protected $dates = [
        "redeemed_at"
    ];
}
