<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name','members'];

    public function members()
    {
        return $this->belongsToMany(User::class,NULL,'team_ids','member_ids');
    }
}
