<?php

namespace App\Http\Resources;

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
        $planName = null;
        $code = null;

        if ($this->appSumoDetails) {
            $publisher = "AppSumo";
            $code = $this->appSumoDetails->code;
        }
        if ($this->appPitchGroundDetails) {
            $publisher = "PitchGround";
            $code = $this->appPitchGroundDetails->code;
            $planName = $this->appPitchGroundDetails->plan;
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
            $publisher = "Free Copoun";
            $code = $this->appCouponCodeDetails->code;
            $plan["ends_at"] = $this->cancelled_at->format("Y-m-d H:i:s");
        }

        $plan['created_at'] = $this->created_at->format("d-m-Y");

        return [
            "account_id" => $this->account_user_id,
            "business_name" => $this->business_name,
            "email" => $this->created_by,
            "user_limit" => $this->user_limit,
            "plan_details" => $plan,
            "publisher" => $publisher,
            "plan_name" => $planName,
            "code_used" => $code,
            "cost" => null,
        ];
    }
}
