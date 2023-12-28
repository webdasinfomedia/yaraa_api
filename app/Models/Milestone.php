<?php

namespace App\Models;

use App\Traits\MarkItem;
use Jenssegers\Mongodb\Eloquent\Model;

class Milestone extends Model
{
    use MarkItem;
    
    protected $fillable = ['title','description','status','due_date','attachments','project_id','task_id','start_date','end_date'];
    
    protected $dates = [
        'due_date',
        'start_date',
        'end_date',
    ];

    protected static function booted()
    {
        static::creating(function($milestone){
            $milestone->status = 'pending';
        });
    }

    public function project(){
        return $this->belongsTo(Project::class);
    }
    
    public function tasks(){
        return $this->belongsToMany(Task::class,NULL,'milestone_ids','task_ids');
    }
    
}
