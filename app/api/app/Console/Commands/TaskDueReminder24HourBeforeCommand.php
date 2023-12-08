<?php

namespace App\Console\Commands;

use App\Mail\SendTaskDue24HourMail;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TaskDueReminder24HourBeforeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:due24before';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send task is due mail notification 24 hours before.';

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
        $tenants = Tenant::all();
        $tenants->each(function ($tenant) {
            $tenant->configure()->use();

            $users = User::all();
            $users->each(function ($user) {
                //fetch not completed task
                $tasks = $user->tasks()->where("status", "!=", "completed")->get();

                foreach ($tasks as $task) {

                    if ($task->due_date) {
                        //check if task is due in 24 or less hours
                        $diff = Carbon::now()->floatDiffInHours($task->due_date);
                        if ($diff <= 24 && !in_array($user->id, ($task->due_24h_mail_send_to ?? []))) {
                            Mail::to($user->email)->send(new SendTaskDue24HourMail($user, $task));
                            $task->push("due_24h_mail_send_to", $user->id);
                        }


                        //Send fcm notification 
                        // $tags = ["NAME" => $task->name, "TIME" => $startDatePretty, "TIMEZONE" => $timezoneAbbreviation];

                        // $notification = Notification::create([
                        //     "title" => "New Reminder : {NAME}",
                        //     "description" =>  "Reminder : {NAME} @ {TIME} ({TIMEZONE})",
                        //     "receiver_ids" => [$user->id],
                        //     "activity_id" => null,
                        //     "ready_by" => [],
                        //     "type" => "todo",
                        //     "module_id" => $todo->id,
                        //     "module" => "Task",
                        //     "tags" => $tags,
                        // ]);

                        // $notification->title = "New Reminder: @ {$startDatePretty} ({$timezoneAbbreviation})";
                        // $notification->description = $todo->name;
                        // $this->sendFcmNotification($notification, [$user->id]);
                    }
                }
            });
        });
    }

    // private function sendFcmNotification(Notification $notification, $recipients)
    // {
    //     $tokens = [];

    //     foreach ($recipients as $recipient) {
    //         $user = User::find($recipient);
    //         $tokens[] = $user->web_fcm_token ? $user->web_fcm_token : null;
    //         $tokens[] = $user->app_fcm_token ? $user->app_fcm_token : null;
    //     }

    //     $tokens = array_values(array_filter(array_unique($tokens)));

    //     $fcmData = [
    //         "body" => [
    //             "id" => $notification->id,
    //             "title" => $notification->title,
    //             "description" => $notification->description,
    //             "created_at" => $notification->created_at,
    //             "type" => $notification->type,
    //             "module_id" => $notification->module_id,
    //             "module" => $notification->module
    //         ],
    //         "deviceTokens" => $tokens
    //     ];

    //     event(new SendFcmNotification($fcmData));
    // }
}
