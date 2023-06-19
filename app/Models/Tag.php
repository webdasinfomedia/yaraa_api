<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name','project_tags','task_tags'];

    public function projects()
    {
        return $this->belongsToMany(Project::class,null,"tags","project_ids");
    }
}
