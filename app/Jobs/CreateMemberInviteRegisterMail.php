<?php

namespace App\Jobs;

use App\Facades\FcmDynamicLink;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendInviteRegisterMail;

class CreateMemberInviteRegisterMail extends Job
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
    public $token;
    public $notes;

    public function __construct($receiver, $sender, $module = 'App', $entityName = "", $token, $notes = null)
    {
        $this->receiver = $receiver;
        $this->sender = $sender;
        $this->module = $module;
        $this->entityName = $entityName;
        $this->token = $token;
        $this->notes = $notes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = env('WEB_URL')."/?invite_member_token={$this->token}&email={$this->receiver->email}";
        $fcmResponse = FcmDynamicLink::create($url);

        if (isset($fcmResponse['shortLink'])) {
            Mail::to($this->receiver)->queue(new SendInviteRegisterMail($this->receiver, $this->sender, $this->module, $this->entityName, $fcmResponse['shortLink']), $this->notes);
        }
    }
}
