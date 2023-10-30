<?php

namespace App\Rules;

use Carbon\Carbon;
use App\Models\CouponCode;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\Rule;

class CouponCodeCheck implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $code = CouponCode::whereCode($value)->first();
        // $today = Carbon::now();
        // $subscriberCount = Tenant::where("provider", Tenant::PROVIDER_ST)->count();
        // $isCodeExpiryValid = $code->expiry_date->greaterThan($today);
        // $isSubscribeLimitCrossed = $subscriberCount <= 200 ? true : false;
        // if ($code && $isCodeExpiryValid && $isSubscribeLimitCrossed) {
        if ($code) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The coupon code is invalid or expired.';
    }
}
