<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class ProjectRole extends Model
{
    protected $fillable = [
        "project_id",
        "user_id",
        "role",
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
