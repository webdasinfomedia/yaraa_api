<?php

namespace App\Jobs;

use App\Mail\SendInvitationMember;
use Illuminate\Support\Facades\Mail;

class InviteMember extends Job
{
    public $user;
    public $sender;
    public $module;
    public $moduleTitle;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $sender, $module, $moduleTitle)
    {
        $this->user = $user;
        $this->sender = $sender;
        $this->module = $module;
        $this->moduleTitle = $moduleTitle;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->user)->send(new SendInvitationMember($this->user, $this->sender, $this->module, $this->moduleTitle));
    }
}
