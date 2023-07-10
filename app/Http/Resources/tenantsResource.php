<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class tenantsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "domain" => $this->domain,
            "business_name" => $this->business_name,
            "database" => $this->database,
            "setup" => $this->setup,
            "created_at" => ($this->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            "user_limit" => $this->user_limit,
            "created_by" => $this->created_by,
            "account_user_id" => $this->account_user_id,
            "cancelled_at" => $this->cancelled_at,
            "business_logo" => $this->business_logo ? url('storage/' . $this->business_logo) : null,
        ];
    }
}
