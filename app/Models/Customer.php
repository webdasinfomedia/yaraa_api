<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'email',
        'mobile_no',
        'additional_details',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, NULL,'customer_ids','project_ids');
    }
}
