<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class StpiCode extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        "code",
        "expiry_date",
        "subscribe_limit"
    ];

    protected $dates = ["expiry_date"];
}
