<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendInviteRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $senderImage;
    public $sender;
    public $module;
    public $moduleTitle;
    public $inviteUrl;
    public $notes;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $sender, $module, $moduleTitle, $url, $notes = null)
    {
        $this->user = $user;
        $this->senderImage = url('storage/'.$sender->image_48x48);
        $this->sender = $sender;
        $this->module = ucfirst($module); //project,task,team
        $this->moduleTitle = ucfirst($moduleTitle); //project,task,team
        $this->inviteUrl = $url;
        $this->notes = $notes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Action Required: {$this->sender->name} invited you to join {$this->module} in Yaraa Manager")
                    ->view('email.invite_register');
    }
}
