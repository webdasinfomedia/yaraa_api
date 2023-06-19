<?php

namespace App\Jobs;

use App\Facades\FcmDynamicLink;
use App\Mail\ForgotPassword;
use Illuminate\Support\Facades\Mail;

class SendForgetPasswordEmail extends Job
{
    public $user;
    public $token;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = env('WEB_URL')."/?resetCode={$this->token}";
        $response = FcmDynamicLink::create($url);
        if(isset($response['shortLink'])){
            Mail::to($this->user)->send(new ForgotPassword($this->user,$this->token,$response['shortLink']));
        }
    }
}
