<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $task;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $task)
    {
        $this->user = $user;
        $this->task = $task;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->task->start_date != null) {
            $startDate = $this->task->start_date->setTimezone($this->user->timezone);
            $startDatePretty = $startDate->format('D M j,Y g:i A');
            $timezoneAbbreviation = getTimezoneAbbreviation($this->user->timezone);

            $subject = "Yaraa Manager : =?utf-8?Q?=E2=8F=B0?= {$this->task->name} @ {$startDatePretty} ({$timezoneAbbreviation})";
            // $date = Carbon::createFromFormat('Y-m-d H:i A', $request->start_date, getUserTimezone());
            // $start_date =
            $body = "New reminder for {$this->task->name}";
            $schedule = "starts at {$startDate} ({$this->user->timezone})";

            return $this->subject($subject)
                ->view('email.reminder')->with(['body' => $body, 'name' => $this->user->name, 'schedule' => $schedule]);
        }
    }
}
