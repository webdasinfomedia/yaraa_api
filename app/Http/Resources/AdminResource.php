<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\Models\LogLocation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $accountUserId = $this->account_user_id ?? null;

        if ($accountUserId != null) {
            //fetch active plan details from accounts
            $url = getAccountsUrl();
            $url .= "/api/apifunction";

            $params = [
                'account_user_id' => $accountUserId,
                'type' => 'getactiveplan',
                'product_slug' => 'yaraa_manager',
                // 'product_slug' => 'niftyyaraamanager',
            ];
            $plan = Http::withOptions(["verify" => false])->post($url, $params)->throw()->json();
        } else {
            $plan = null;
        }

        $publisher = $this->account_user_id ? "NiftySol" : null;
        $publisher = $this->subscription_id ? "Stripe" : $publisher;
        $code = null;
        $plan["ends_at"] = $this->cancelled_at->format("Y-m-d H:i:s");
        
        if ($this->appSumoDetails) {
            $publisher = "AppSumo";
            $code = $this->appSumoDetails->code;
        }
        if ($this->appPitchGroundDetails) {
            $publisher = "PitchGround";
            $code = $this->appPitchGroundDetails->code;
            $plan['name'] = $this->appPitchGroundDetails->plan;
        }
        if ($this->appDealFuelDetails) {
            $publisher = "DealFuel";
            $code = $this->appDealFuelDetails->code;
        }
        if ($this->appStpiDetails) {
            $publisher = "STPI";
            $code = $this->appStpiDetails->code;
        }
        if ($this->appCouponCodeDetails) {
            $publisher = "Free Coupon";
            $code = $this->appCouponCodeDetails->code;
            $plan["ends_at"] = $this->cancelled_at->format("Y-m-d H:i:s");
        }

        $plan['created_at'] = $this->created_at->format("d-m-Y");

        $this->configure()->use();
        
        $location = LogLocation::where("email",$this->created_by)->first();
        if($location){
            $location = getLocationFromCoordinates($location->latitude,$location->longitude);
        }

        $activityData = null;
        $activity =  Activity::orderBy("activity_time","desc")->first();
        if($activity){
            $activityData['activity'] = $activity->activity;
            $activityData['activity_at'] = $activity->activity_time;
        }

        return [
            "account_id" => $this->account_user_id,
            "business_name" => $this->business_name,
            "email" => $this->created_by,
            "user_limit" => $this->user_limit,
            "plan_details" => $plan,
            "plan_name" => $plan['name'] ?? null,
            "publisher" => $publisher,
            "code_used" => $code,
            "cost" => null,
            "phone" => $this->country ? $this->country . "-" . $this->phone : null,
            "location" => $location,
            "recent_activity" => $activityData
        ];

    }
}
