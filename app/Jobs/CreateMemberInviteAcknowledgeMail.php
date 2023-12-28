<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendInviteAcknowledgeMail;

class CreateMemberInviteAcknowledgeMail extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $receiver;
    public $sender;
    public $module;
    public $entityName;
    public $dueDate;
    public $totalMembers;
    public $description;
    public $priority;

    public function __construct($receiver, $sender, $module = 'App', $entityName = "", $dueDate, $totalMembers, $description, $priority = null)
    {
        $this->receiver = $receiver;
        $this->sender = $sender;
        $this->module = $module;
        $this->entityName = $entityName;
        $this->dueDate = $dueDate;
        $this->totalMembers = $totalMembers;
        $this->description = $description;
        $this->priority = $priority;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->receiver)->send(new SendInviteAcknowledgeMail($this->receiver, $this->sender, $this->module, $this->entityName, $this->dueDate, $this->totalMembers, $this->description, $this->priority));
    }
}
