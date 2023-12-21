<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendInviteAcknowledgeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $user;
    public $senderImage;
    public $sender;
    public $module;
    public $moduleTitle;
    public $fbUrl;
    public $instaUrl;
    public $twUrl;
    public $liUrl;
    public $ytUrl;
    public $yaraaLogoHeader;
    public $yaraaLogoFooter;
    public $clockUrl;
    public $calUrl;
    public $personUrl;
    public $dueTime;
    public $dueDate;
    public $totalMembers;
    public $description;
    public $priority;

    public function __construct($user, $sender, $module, $moduleTitle, $dueDateTime, $totalMembers, $description, $priority)
    {
        $this->user = $user;
        $this->senderImage = $sender->image_48x48 != null ? url('storage/' . $sender->image_48x48) : url('storage/' . $sender->image);
        $this->sender = $sender;
        $this->module = ucfirst($module); //project,task,team
        $this->moduleTitle = ucfirst($moduleTitle); //project,task,team
        $this->dueTime = $dueDateTime ? $dueDateTime->format('H:i') : '-';
        $this->dueDate = $dueDateTime ? $dueDateTime->format('Y-m-d') : '-';
        $this->totalMembers = $totalMembers;
        $this->description = trim($description) ?? null;
        $this->priority = ucwords($priority);

        $this->fbUrl = url('storage/mail-images/facebook.png');
        $this->instaUrl = url('storage/mail-images/instagram.png');
        $this->twUrl = url('storage/mail-images/twiter.png');
        $this->liUrl = url('storage/mail-images/linkin.png');
        $this->ytUrl = url('storage/mail-images/youtub.png');
        $this->yaraaLogoHeader = url('storage/mail-images/yaraa_logo_240x100.png');
        $this->yaraaLogoFooter = url('storage/mail-images/yaraa_logo_208x94.png');
        $this->clockUrl = url('storage/mail-images/watch.png');
        $this->calUrl = url('storage/mail-images/calendar.png');
        $this->personUrl = url('storage/mail-images/person.png');
    }

    /**
     * Build the message.
     *
     * @return $this 
     */
    public function build()
    {
        return $this->subject("{$this->sender->name} added you to {$this->module} in Yaraa Manager")
            ->view('email.invite_acknowledge');
    }
}
