<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\User;
use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Builder;

class AdminTeamController extends Controller
{
    public function getMemberList()
    {
        $users = User::withTrashed()->whereHas('role', function (Builder $query) {
            $query->where('slug', 'employee')->orWhere('slug', 'admin');
        })->get();

        return MemberResource::collection($users)->additional(['error' => false, 'message' => null]);
    }
}
