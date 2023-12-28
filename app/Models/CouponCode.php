<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class CouponCode extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        "code",
        "expiry_date",
        "subscribe_limit",
        "user_limit",
        "subscription_days_limit"  //no. of days
    ];

    protected $dates = ["expiry_date"];
}
