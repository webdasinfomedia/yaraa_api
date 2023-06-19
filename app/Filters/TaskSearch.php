<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class TaskSearch
{

    public static function apply($filter, Builder $builder)
    {
        dd($builder);
    }
}
