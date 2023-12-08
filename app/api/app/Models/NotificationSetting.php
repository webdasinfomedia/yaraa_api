<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $hidden = ['_id', 'created_at', 'updated_at'];

    protected $fillable = [
        'email',
        'push',
        'task_created',
        'task_completed',
        'task_uncompleted',
        'task_comment',
        'task_deleted',
        'task_restored',
        'task_invite_member',
        'project_created',
        'project_invite_member',
        'project_completed',
        'project_reopen',
        'project_deleted',
        'project_restored',
        'message_notify_recipients',
        'milestone_created',
        'milestone_completed',
        'milestone_reopen',
        'milestone_deleted',
        'customer_project_created',
        'customer_project_completed',
        'customer_project_reopen',
        'customer_task_created',
        'customer_task_completed',
        'customer_task_reopen',
    ];
}
