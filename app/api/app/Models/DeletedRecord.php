<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class DeletedRecord extends Model
{
    protected $fillable = [
        "model",
        "name",
        "data"
    ];
}
