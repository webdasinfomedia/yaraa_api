<?php

namespace App\Console\Commands;

use App\Events\SendFcmNotification;
use App\Mail\ReminderMail;
use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTodoReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todo_reminder:send';

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

            User::all()->each(function ($user) {
                $user->todos()->each(function ($todo) use ($user) {
                    if ($todo->reminder == true && $todo->reminder_sent == false) {
                        $diff = Carbon::now()->floatDiffInMinutes($todo->start_date, false);

                        if ($diff > 0 && $diff < 10) {
                            Mail::to($user->email)->send(new ReminderMail($user, $todo));

                            $timezoneAbbreviation = getTimezoneAbbreviation($user->timezone);
                            $startDatePretty = $todo->start_date->setTimezone($user->timezone)->format('D M j,Y g:i A');

                            $tags = ["NAME" => $todo->name, "TIME" => $startDatePretty, "TIMEZONE" => $timezoneAbbreviation];

                            $notification = Notification::create([
                                "title" => "New Reminder : {NAME}",
                                "description" =>  "Reminder : {NAME} @ {TIME} ({TIMEZONE})",
                                "receiver_ids" => [$user->id],
                                "activity_id" => null,
                                "ready_by" => [],
                                "type" => "todo",
                                "module_id" => $todo->id,
                                "module" => "Task",
                                "tags" => $tags,
                            ]);

                            $notification->title = "New Reminder: @ {$startDatePretty} ({$timezoneAbbreviation})";
                            $notification->description = $todo->name;
                            $this->sendFcmNotification($notification, [$user->id]);

                            $todo->reminder_sent = true;
                            $todo->save();
                        }
                    }
                });
            });
        });
    }

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
