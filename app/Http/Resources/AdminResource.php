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
            $plan = "No plan found"; 
        }

        return [
            "account_id" => $this->account_user_id,
            "business_name" => $this->business_name,
            "email" => $this->created_by,
            "user_limit" => $this->user_limit,
            "plan_details" => $plan,
            "publisher" => null,
            "plan_name" => null,
            "cost" => null,
        ];
    }
}
