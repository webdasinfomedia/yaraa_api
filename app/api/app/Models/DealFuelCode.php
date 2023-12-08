<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class DealFuelCode extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        "code",
        "redeemed",
        "redeemed_at",
    ];

    protected $dates = [
        "redeemed_at"
    ];
}
