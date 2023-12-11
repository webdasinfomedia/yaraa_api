<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;
    
    public $user;
    public $token;
    public $fcmShortLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $token, $shortLink)
    {
        $this->user = $user;
        $this->token = $token;
        $this->fcmShortLink = $shortLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Reset password link')
                    ->view('auth.password.reset');
    }
}
