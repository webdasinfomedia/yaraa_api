<?php

namespace App\Rules;

use App\Models\AppSumoCode;
use App\Models\PitchGroundCode;
use Illuminate\Contracts\Validation\Rule;

class PitchGroundCodeCheck implements Rule
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
        $code = PitchGroundCode::whereCode($value)->first();
        if ($code && $code->redeemed == 'no') {
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
        return 'The code is invalid or expired.';
    }
}
