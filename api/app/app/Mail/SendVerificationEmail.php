<?php

namespace App\Mail;

use App\Facades\FcmDynamicLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendVerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verifyLink;
    public $url;
    public $response;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user ,$token)
    {
        $this->user = $user;
        $this->url = env('WEB_URL')."/?verify_email_code={$token}";
        $this->response = FcmDynamicLink::create($this->url);
        $this->verifyLink = $this->response['shortLink'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify your account | Yaraa Manager')
                    ->view('email.verify');
    }
}
