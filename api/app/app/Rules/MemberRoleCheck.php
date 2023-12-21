<?php

namespace App\Rules;

use App\Models\Project;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class MemberRoleCheck implements Rule
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
        $members = json_decode($value, true);
        $roles = Arr::pluck($members, 'role');
        $allRoles = [
            Project::CAN_EDIT,
            Project::CAN_COMMENT,
            Project::CAN_VIEW,
        ];

        return count(array_intersect($roles, $allRoles)) == count($roles) ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid role passed';
    }
}
