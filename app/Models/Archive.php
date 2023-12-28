<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Archive extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'module_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
