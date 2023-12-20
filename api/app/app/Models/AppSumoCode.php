<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class AppSumoCode extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        "code",
        "redeemed",
        "redeemed_at"
    ];

    protected $dates = [
        "redeemed_at"
    ];
}
