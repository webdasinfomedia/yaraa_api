<?php

namespace App\Console\Commands;

use App\Events\SendFcmNotification;
use App\Mail\ReminderMail;
use App\Models\Notification;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTaskReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task_reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check & Send reminder before 10 minutes for todos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Tenant::all()->each(function ($tenant) {

            $tenant->configure()->use();

            Task::where('reminder', true)->where('reminder_sent', '!=', true)->where('status', '!=', 'completed')->each(function ($task) {
                if ($task->start_date != null) {
                    $task->assignedTo()->each(function ($user) use ($task) {
                        $diff = Carbon::now()->floatDiffInMinutes($task->start_date, false);
                        if ($diff > 0 && $diff < 10) {
                            Mail::to($user->email)->send(new ReminderMail($user, $task));

                            $timezoneAbbreviation = getTimezoneAbbreviation($user->timezone);
                            $startDatePretty = $task->start_date->setTimezone($user->timezone)->format('D M j,Y g:i A');

                            $tags = ["NAME" => $task->name, "TIME" => $startDatePretty, "TIMEZONE" => $timezoneAbbreviation];
                            $notification = Notification::create([
                                "title" => "Reminder : {NAME}",
                                "description" => "Reminder : {NAME} @ {TIME} ({TIMEZONE})",
                                "receiver_ids" => [$user->id],
                                "activity_id" => null,
                                "ready_by" => [],
                                "type" => "task",
                                "module_id" => $task->id,
                                "module" => "Task",
                                "tags" => $tags,
                            ]);

                            $notification->title = "New Reminder: @ {$startDatePretty} ({$timezoneAbbreviation})";
                            $notification->description = $task->name;
                            $this->sendFcmNotification($notification, [$user->id]);

                            $task->reminder_sent = true;
                            $task->save();
                        }
                    });
                }
            });
        });
    }

    /*public function handle()
    {
        Tenant::all()->each(function ($tenant) {

            $tenant->configure()->use();

            User::all()->each(function ($user) {
                $user->tasks()
                    ->where('reminder', true)
                    ->where('reminder_sent', '!=', true)
                    ->where('status', '!=', 'completed')
                    ->each(function ($task) use ($user) {
                        // $user->tasks()->each(function ($task) use ($user) {
                        // if ($task->reminder == true && $task->reminder_sent == false) {
                        $diff = Carbon::now()->floatDiffInMinutes($task->start_date, false);

                        if ($diff > 0 && $diff < 10) {
                            Mail::to($user->email)->send(new ReminderMail($user, $task));

                            $timezoneAbbreviation = getTimezoneAbbreviation($user->timezone);
                            $startDatePretty = $task->start_date->setTimezone($user->timezone)->format('D M j,Y g:i A');

                            $tags = ["NAME" => $task->name, "TIME" => $startDatePretty, "TIMEZONE" => $timezoneAbbreviation];
                            $notification = Notification::create([
                                "title" => "Reminder : {NAME}",
                                "description" => "Reminder : {NAME} @ {TIME} ({TIMEZONE})",
                                "receiver_ids" => [$user->id],
                                "activity_id" => null,
                                "ready_by" => [],
                                "type" => "task",
                                "module_id" => $task->id,
                                "module" => "Task",
                                "tags" => $tags,
                            ]);


                            $notification->title = "New Reminder: @ {$startDatePretty} ({$timezoneAbbreviation})";
                            $notification->description = $task->name;
                            $this->sendFcmNotification($notification, [$user->id]);

                            $task->reminder_sent = true;
                            $task->save();
                        }
                        // }
                    });
            });
        });
    }
    */

    private function sendFcmNotification(Notification $notification, $recipients)
    {
        $tokens = [];

        foreach ($recipients as $recipient) {
            $user = User::find($recipient);
            $tokens[] = $user->web_fcm_token ? $user->web_fcm_token : null;
            $tokens[] = $user->app_fcm_token ? $user->app_fcm_token : null;
        }
        $tokens = array_values(array_filter(array_unique($tokens)));

        $fcmData = [
            "body" => [
                "id" => $notification->id,
                "title" => $notification->title,
                "description" => $notification->description,
                "created_at" => $notification->created_at,
                "type" => $notification->type,
                "module_id" => $notification->module_id,
                "module" => $notification->module
            ],
            "deviceTokens" => $tokens
        ];

        event(new SendFcmNotification($fcmData));
    }
}
