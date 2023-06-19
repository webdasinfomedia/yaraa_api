<?php

namespace App\Services;

use App\Mail\CustomerMail;
use App\Models\FailedActivity;
use Illuminate\Support\Facades\Mail;

class CustomerService
{
    public function __call($function, $args)
    {
        $errorData = $function . ' method not found';
        FailedActivity::create(['error_data' => $errorData, 'activity_data' => json_encode($args)]);
        return $errorData;
    }

    public function created($activity)
    {
        $this->activity_data = json_decode($activity['activity_data'], true);

        /** Send Mail notification to Customer */
        if (getNotificationSettings('email') && getNotificationSettings('customer_project_created')) {
            Mail::to($this->activity_data['email'])->send(new CustomerMail($this->activity_data['name'], null, 'customer_created', null, $this->activity_data));
        }
    }
}
