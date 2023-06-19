<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;


class Timeline extends Model
{
    protected $fillable = [
        "module",
        "module_id",
        "project_id",
        "status",
        "created_by",
        "description",
        "activity_at",
    ];

    protected $dates = [
        "activity_at"
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(getDefaultUserModel());
    }
}
