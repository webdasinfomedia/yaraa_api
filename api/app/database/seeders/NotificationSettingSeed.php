<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationSettingSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\NotificationSetting::truncate();

        \App\Models\NotificationSetting::create([
            'email' => true,
            'push' => true,
            'task_created' => true,
            'task_completed' => true,
            'task_uncompleted' => true,
            'task_comment' => true,
            'task_deleted' => true,
            'task_restored' => true,
            'task_invite_member' => true,
            'project_created' => true,
            'project_invite_member' => true,
            'project_completed' => true,
            'project_reopen' => true,
            'project_deleted' => true,
            'project_restored' => true,
            'message_notify_recipients' => true,
            'milestone_created' => true,
            'milestone_completed' => true,
            'milestone_reopen' => true,
            'milestone_deleted' => true,
            'customer_project_created' => true,
            'customer_project_completed' => true,
            'customer_project_reopen' => true,
            'customer_task_created' => true,
            'customer_task_completed' => true,
            'customer_task_reopen' => true,
        ]);
    }
}
