<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Priority extends Model
{
    protected $fillable = ['name','color'];

    public static function getColor($slug = null)
    {
        return self::where('slug',$slug)->pluck('color')->first() ?? '#000000';
    }
}
