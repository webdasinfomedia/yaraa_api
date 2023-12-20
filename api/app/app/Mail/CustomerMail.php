<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $moduleName;
    public $type;
    public $activityDate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customerName, $moduleName, $type, $activityDate = null, $activityData = [])
    {
        $this->customerName = $customerName;
        $this->moduleName = $moduleName;
        $this->type = $type;
        $this->activityDate = $activityDate ? $activityDate->format('F j, Y') : null;
        $this->activityData = $activityData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $organization = app()->tenant->business_name;

        switch ($this->type) {
            case 'customer_created':
                $subject = "Yaraa Manager: You have been added to organization {$organization}";
                $body = "You are added in organization {$organization}, You will receive mail notification once you are added in a project.";
                $body .= "<p>You Can login with below details</p>";
                $body .= "<p>Email:{$this->activityData['email']}</p>";
                $body .= "<p>password:{$this->activityData['password']}</p>";
                break;
            case 'project_created':
                $subject = "Yaraa Manager : {$this->moduleName} Project is Created";
                $body = $this->moduleName . " project has been created on " . $this->activityDate;
                break;
            case 'project_completed':
                $subject = "Yaraa Manager : {$this->moduleName} Project is Completed";
                $body = $this->moduleName . " project has been completed on " . $this->activityDate;
                break;
            case 'project_reopen':
                $subject = "Yaraa Manager : {$this->moduleName} Project is Re-open";
                $body = $this->moduleName . " project has been re-open on " . $this->activityDate;
                break;
            case 'task_created':
                $subject = "Yaraa Manager : {$this->moduleName} Task is Created";
                $body = $this->moduleName . " task has been created on " . $this->activityDate;
                break;
            case 'task_completed':
                $subject = "Yaraa Manager : {$this->moduleName} Task is Completed";
                $body = $this->moduleName . " task has been completed on " . $this->activityDate;
                break;
            case 'task_reopen':
                $subject = "Yaraa Manager : {$this->moduleName} Task is Re-open";
                $body = $this->moduleName . " task has been re-open on " . $this->activityDate;
                break;
        }

        return $this->subject($subject)
            ->view('email.customer')->with(['body' => $body]);
    }
}
