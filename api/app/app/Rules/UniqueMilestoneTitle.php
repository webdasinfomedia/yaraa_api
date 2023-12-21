<?php

namespace App\Rules;

use App\Models\Milestone;
use Illuminate\Contracts\Validation\Rule;

class UniqueMilestoneTitle implements Rule
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
        // var_dump($attribute);
        $milestone = Milestone::find(request('id'));
        $projectId = $milestone->project_id;

        $titleExists = Milestone::where('title',$value)->where('project_id',$projectId)->where('_id','!=',request('id'))->exists();
        return $titleExists ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Title has already been taken';
    }
}
