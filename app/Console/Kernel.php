<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\GenerateUserDefaultImage::class,
        \App\Console\Commands\AddDefaultStatusToProjectAndTask::class,
        \App\Console\Commands\addArchivedByColumn::class,
        \App\Console\Commands\TenantsMigrateCommand::class,
        \App\Console\Commands\SendTodoReminderCommand::class,
        \App\Console\Commands\SendTaskReminderCommand::class,
        \App\Console\Commands\CreateRecurringTask::class,
        \App\Console\Commands\AddProjectDefaultRole::class,
        \App\Console\Commands\CreateRecurringTodo::class,
        \App\Console\Commands\DeleteEmptyMemberTasksCommand::class,
        \App\Console\Commands\AutoPunchOutEodCommand::class,
        \App\Console\Commands\AddCustomerRoleCommand::class,
        \App\Console\Commands\TaskDueReminder24HourBeforeCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(\App\Console\Commands\SendTodoReminderCommand::class)->everyMinute();
        $schedule->command(\App\Console\Commands\SendTaskReminderCommand::class)->everyMinute();
        $schedule->command(\App\Console\Commands\CreateRecurringTask::class)->daily();
        $schedule->command(\App\Console\Commands\CreateRecurringTodo::class)->daily();
        $schedule->command(\App\Console\Commands\DeleteEmptyMemberTasksCommand::class)->daily();
        $schedule->command(\App\Console\Commands\AutoPunchOutEodCommand::class)->dailyAt('23:15');
        $schedule->command(\App\Console\Commands\TaskDueReminder24HourBeforeCommand::class)->everyTenMinutes();
    }
}
