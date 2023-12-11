<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTaskDue24HourMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $task;
    public $body;
    public $url;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $task)
    {
        $this->userName = $user->name;
        $this->task = $task;

        $timezone = $user->timezone ?? 'UTC';
        $dueDate = $this->task->due_date->setTimezone($timezone);
        $dueDatePretty = $dueDate->format('D M j,Y g:i A');
        $timezoneAbbreviation = getTimezoneAbbreviation($timezone);

        $this->body = "Your task {$task->name} will be due at {$dueDatePretty} ({$timezoneAbbreviation}).";
        $this->url = env('WEB_URL');
        $this->subject = "Yaraa Manager : =?utf-8?Q?=E2=8F=B0?= Task: {$this->task->name} is Due in 24 Hours";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('view.name');
        return $this->subject($this->subject)
            ->view('email.task_due_24h_reminder');
    }
}
